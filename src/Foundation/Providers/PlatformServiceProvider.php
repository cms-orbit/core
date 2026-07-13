<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Providers;

use CmsOrbit\Core\Foundation\ItemPermission;
use CmsOrbit\Core\Foundation\Orbit;
use CmsOrbit\Core\Foundation\Permissions\SuperAdminPermissionSync;
use Illuminate\Support\ServiceProvider;

class PlatformServiceProvider extends ServiceProvider
{
    protected Orbit $orbit;

    /**
     * Boot the application events.
     */
    public function boot(Orbit $orbit): void
    {
        $this->orbit = $orbit;

        $this->app->booted(function () {
            $this->orbit
                ->registerResource('stylesheets', config('orbit.resource.stylesheets'))
                ->registerResource('scripts', config('orbit.resource.scripts'))
                ->registerSearch(config('orbit.search', []))
                ->registerPermissions($this->registerPermissionsMain())
                ->registerPermissions($this->registerPermissionsSystems());

            // Runs after every provider's booted callbacks so entity, config,
            // auth, and platform permissions are all present in the registry.
            app(SuperAdminPermissionSync::class)->sync();
        });
    }

    protected function registerPermissionsMain(): ItemPermission
    {
        return ItemPermission::group(__('Main'))
            ->addPermission('orbit.index', __('Main'));
    }

    protected function registerPermissionsSystems(): ItemPermission
    {
        return ItemPermission::group(__('System'))
            ->addPermission('orbit.attachment', __('Attachment'));
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $provider = config('orbit.provider', PlatformProvider::class);

        if ($provider !== null && class_exists($provider)) {
            $this->app->register($provider);
        }
    }
}
