<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Settings\Providers;

use Illuminate\Support\ServiceProvider;
use CmsOrbit\Core\Foundation\Icons\IconFinder;
use CmsOrbit\Core\Settings\Dashboard;
use CmsOrbit\Core\Settings\ItemPermission;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * @var Dashboard
     */
    protected $dashboard;

    /**
     * Boot the application events.
     */
    public function boot(Dashboard $dashboard, IconFinder $iconFinder): void
    {
        $this->dashboard = $dashboard;

        foreach (config('orbit.icons', []) as $key => $path) {
            $iconFinder->registerIconDirectory($key, is_callable($path) ? $path() : $path);
        }

        $this->app->booted(function () {
            $this->dashboard
                ->registerResource('stylesheets', config('orbit.resource.stylesheets'))
                ->registerResource('scripts', config('orbit.resource.scripts'))
                ->registerSearch(config('orbit.search', []))
                ->registerPermission($this->registerPermissionsMain())
                ->registerPermission($this->registerPermissionsSystems());
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
            ->addPermission('orbit.systems.attachment', __('Attachment'));
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $provider = config('orbit.provider', \App\CmsOrbit\Core\SettingsProvider::class);

        if ($provider !== null && class_exists($provider)) {
            $this->app->register($provider);
        }
    }
}
