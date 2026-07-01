<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Media;

use CmsOrbit\Core\Attachment\File;
use CmsOrbit\Core\Attachment\Models\Attachment;
use CmsOrbit\Core\Media\Jobs\EncodeVideoJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Upload + post-processing front door for the media library. Wraps the ported
 * Attachment upload pipeline with image resizing and ffmpeg-based video
 * encoding driven by the Media config group.
 */
class MediaLibrary
{
    /**
     * Upload a file and run kind-specific post-processing.
     */
    public function upload(UploadedFile $file, ?string $group = 'media'): Attachment
    {
        /** @var Attachment $attachment */
        $attachment = (new File($file, null, $group))->load();

        $kind = $this->detectKind($attachment->mime);
        $attachment->kind = $kind;

        match ($kind) {
            'image' => $this->processImage($attachment),
            'video' => $this->processVideo($attachment),
            'audio' => $attachment->encoding_status = 'done',
            default => $attachment->encoding_status = 'done',
        };

        $attachment->save();

        return $attachment;
    }

    /**
     * Classify an attachment by mime type.
     */
    public function detectKind(?string $mime): string
    {
        return match (true) {
            Str::startsWith((string) $mime, 'image/') => 'image',
            Str::startsWith((string) $mime, 'video/') => 'video',
            Str::startsWith((string) $mime, 'audio/') => 'audio',
            default => 'file',
        };
    }

    /**
     * Resize/re-encode images to the configured max width / quality.
     */
    protected function processImage(Attachment $attachment): void
    {
        $attachment->encoding_status = 'done';

        if (! class_exists(ImageManager::class)) {
            return;
        }

        $disk = Storage::disk($attachment->disk);
        $path = $attachment->physicalPath();

        if ($path === null || ! $disk->exists($path)) {
            return;
        }

        $maxWidth = (int) orbit_config('media.image_max_width', 1200);
        $quality = (int) orbit_config('media.image_quality', 100);

        try {
            $manager = new ImageManager(
                new Driver
            );

            $image = $manager->read($disk->get($path));

            $attachment->width = $image->width();
            $attachment->height = $image->height();

            if ($image->width() > $maxWidth) {
                $image->scaleDown(width: $maxWidth);
                $attachment->width = $image->width();
                $attachment->height = $image->height();
            }

            $disk->put($path, (string) $image->encodeByPath($path, quality: $quality));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Queue an encoding job when ffmpeg is available, otherwise keep the
     * original and flag it as skipped.
     */
    protected function processVideo(Attachment $attachment): void
    {
        if (! $this->ffmpegAvailable()) {
            $attachment->encoding_status = 'skipped';

            return;
        }

        $attachment->encoding_status = 'pending';
        $attachment->saveQuietly();

        EncodeVideoJob::dispatch($attachment->getKey());
    }

    /**
     * Detect a usable ffmpeg binary via `which ffmpeg`.
     */
    public function ffmpegAvailable(): bool
    {
        static $available = null;

        if ($available !== null) {
            return $available;
        }

        $path = @shell_exec('which ffmpeg');

        return $available = ! empty(trim((string) $path));
    }
}
