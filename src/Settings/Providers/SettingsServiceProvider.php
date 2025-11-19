<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Providers;

use Illuminate\Support\ServiceProvider;
use CmsOrbit\Core\Icons\IconFinder;
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

        foreach (config('settings.icons', []) as $key => $path) {
            $iconFinder->registerIconDirectory($key, $path);
        }

        $this->app->booted(function () {
            $this->dashboard
                ->registerResource('stylesheets', config('settings.resource.stylesheets'))
                ->registerResource('scripts', config('settings.resource.scripts'))
                ->registerSearch(config('settings.search', []))
                ->registerPermission($this->registerPermissionsMain())
                ->registerPermission($this->registerPermissionsSystems());
        });
    }

    protected function registerPermissionsMain(): ItemPermission
    {
        return ItemPermission::group(__('Main'))
            ->addPermission('settings.index', __('Main'));
    }

    protected function registerPermissionsSystems(): ItemPermission
    {
        return ItemPermission::group(__('System'))
            ->addPermission('settings.systems.attachment', __('Attachment'));
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $provider = config('settings.provider', \App\CmsOrbit\Core\SettingsProvider::class);

        if ($provider !== null && class_exists($provider)) {
            $this->app->register($provider);
        }
    }
}
