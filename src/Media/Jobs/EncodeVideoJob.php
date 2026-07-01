<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Media\Jobs;

use CmsOrbit\Core\Attachment\Models\Attachment;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Transcodes an uploaded video to the admin-configured resolution / bitrate /
 * format using ffmpeg. Falls back to flagging failure on error.
 */
class EncodeVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function __construct(public string $attachmentId) {}

    public function handle(): void
    {
        /** @var Attachment|null $attachment */
        $attachment = Orbit::model(Attachment::class)::find($this->attachmentId);

        if ($attachment === null) {
            return;
        }

        $attachment->encoding_status = 'processing';
        $attachment->saveQuietly();

        $disk = Storage::disk($attachment->disk);
        $source = $disk->path($attachment->physicalPath());

        $format = (string) orbit_config('media.video_format', 'mp4');
        $bitrate = (string) orbit_config('media.video_bitrate', '2500k');
        $resolution = $this->resolutionHeight((string) orbit_config('media.video_resolution', '720p'));

        $target = preg_replace('/\.[^.]+$/', '', $source).'-encoded.'.$format;

        $process = new Process([
            'ffmpeg', '-y', '-i', $source,
            '-vf', 'scale=-2:'.$resolution,
            '-b:v', $bitrate,
            $target,
        ]);
        $process->setTimeout($this->timeout);

        try {
            $process->run();

            $attachment->encoding_status = $process->isSuccessful() ? 'done' : 'failed';
            $attachment->meta = array_merge((array) $attachment->meta, [
                'encoded_path' => $process->isSuccessful() ? $target : null,
            ]);
        } catch (\Throwable $exception) {
            report($exception);
            $attachment->encoding_status = 'failed';
        }

        $attachment->saveQuietly();
    }

    protected function resolutionHeight(string $resolution): int
    {
        return match ($resolution) {
            '480p' => 480,
            '1080p' => 1080,
            default => 720,
        };
    }
}
