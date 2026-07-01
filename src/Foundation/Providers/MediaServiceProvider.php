<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Providers;

use CmsOrbit\Core\Media\MediaLibrary;
use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MediaLibrary::class, fn () => new MediaLibrary);
    }
}
