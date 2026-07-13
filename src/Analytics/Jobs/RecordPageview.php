<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Analytics\Jobs;

use CmsOrbit\Core\Analytics\Models\AnalyticsPageview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class RecordPageview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(public array $attributes) {}

    public function handle(): void
    {
        AnalyticsPageview::query()->create($this->attributes);
    }
}
