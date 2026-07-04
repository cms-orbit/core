<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Media;

use CmsOrbit\Core\Attachment\File;
use CmsOrbit\Core\Attachment\Models\Attachment;
use CmsOrbit\Core\Media\Jobs\EncodeVideoJob;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Str as SupportStr;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

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
    public function upload(UploadedFile $file, ?string $group = 'media', array $options = []): Attachment
    {
        $disk = filled($options['storage'] ?? null) ? (string) $options['storage'] : null;
        $purpose = filled($options['purpose'] ?? null) ? (string) $options['purpose'] : null;
        $path = filled($options['path'] ?? null) ? (string) $options['path'] : null;

        /** @var Attachment $attachment */
        $attachment = (new File($file, $disk, $group))
            ->path($path)
            ->load();

        $kind = $this->detectKind($attachment->mime);
        $attachment->kind = $kind;
        $attachment->meta = array_filter([
            ...((array) $attachment->meta),
            'purpose' => $purpose,
        ]);

        match ($kind) {
            'image' => $this->processImage($attachment, $purpose),
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
            default                                   => 'file',
        };
    }

    /**
     * Resize/re-encode images to the configured max width / quality.
     */
    protected function processImage(Attachment $attachment, ?string $purpose = null): void
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
            $attachment->size = $disk->size($path);

            if ($attachment->group === 'branding' && $purpose === 'favicon') {
                $this->generateFaviconVariants($attachment, $manager, $disk, $image, $path);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    protected function generateFaviconVariants(
        Attachment $attachment,
        ImageManager $manager,
        Filesystem $disk,
        ImageInterface $image,
        string $path,
    ): void {
        $directory = SupportStr::finish(pathinfo($path, PATHINFO_DIRNAME), '/');
        $basename = pathinfo($path, PATHINFO_FILENAME);
        $variantPrefix = "{$basename}-{$attachment->getKey()}";

        $variants = [
            'icon16'     => ['file' => "{$variantPrefix}-16x16.png", 'size' => 16, 'rel' => 'icon', 'sizes' => '16x16'],
            'icon32'     => ['file' => "{$variantPrefix}-32x32.png", 'size' => 32, 'rel' => 'icon', 'sizes' => '32x32'],
            'appleTouch' => ['file' => "{$variantPrefix}-apple-touch-icon.png", 'size' => 180, 'rel' => 'apple-touch-icon', 'sizes' => '180x180'],
            'icon192'    => ['file' => "{$variantPrefix}-192x192.png", 'size' => 192, 'rel' => 'icon', 'sizes' => '192x192'],
            'icon512'    => ['file' => "{$variantPrefix}-512x512.png", 'size' => 512, 'rel' => 'icon', 'sizes' => '512x512'],
        ];

        $manifestPath = $directory."{$variantPrefix}-site.webmanifest";
        $generatedFiles = [];
        $resolved = [];

        foreach ($variants as $key => $variant) {
            $variantPath = $directory.$variant['file'];
            $copy = $manager->read($image->encodeByMediaType('image/png'));
            $copy->cover($variant['size'], $variant['size']);
            $disk->put($variantPath, (string) $copy->encodeByMediaType('image/png'));
            $generatedFiles[] = $variantPath;
            $resolved[$key] = $disk->url($variantPath);
        }

        $manifest = [
            'name'       => config('app.name'),
            'short_name' => config('app.name'),
            'icons'      => [
                [
                    'src'   => $resolved['icon192'],
                    'sizes' => '192x192',
                    'type'  => 'image/png',
                ],
                [
                    'src'   => $resolved['icon512'],
                    'sizes' => '512x512',
                    'type'  => 'image/png',
                ],
            ],
            'theme_color'      => '#ffffff',
            'background_color' => '#ffffff',
            'display'          => 'standalone',
        ];

        $disk->put($manifestPath, json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        $generatedFiles[] = $manifestPath;

        $attachment->meta = [
            ...((array) $attachment->meta),
            'generated_files'  => $generatedFiles,
            'favicon_variants' => [
                ...$resolved,
                'ico'      => $resolved['icon32'],
                'manifest' => $disk->url($manifestPath),
            ],
        ];
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
