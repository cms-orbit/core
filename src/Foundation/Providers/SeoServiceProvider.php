<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Providers;

use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Foundation\Entity\EntityRegistry;
use CmsOrbit\Core\Seo\SitemapRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the sitemap registry and auto-registers each entity's sitemap URLs.
 */
class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SitemapRegistry::class, fn () => new SitemapRegistry);
    }

    public function boot(): void
    {
        $this->app->booted(function () {
            $sitemap = app(SitemapRegistry::class);

            app(EntityRegistry::class)->all()->each(function (Entity $entity) use ($sitemap) {
                $sitemap->register(fn () => $entity->sitemapUrls());
            });
        });
    }
}
