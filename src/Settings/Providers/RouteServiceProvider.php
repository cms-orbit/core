<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use CmsOrbit\Core\Settings\Http\Middleware\Access;
use CmsOrbit\Core\Settings\Http\Middleware\BladeIcons;
use CmsOrbit\Core\Settings\Http\Middleware\Turbo;
use CmsOrbit\Core\Support\Facades\Dashboard;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @internal param Router $router
     */
    public function boot()
    {
        Route::middlewareGroup('settings', [
            Turbo::class,
            BladeIcons::class,
            Access::class,
        ]);

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        /*
         * Dashboard routes.
         *
         * The dashboard routes have a subdomain of the settings.domain config value,
         * a prefix consisting of the Dashboard::prefix() method return value,
         * an alias of 'settings.', middleware from the settings.middleware.private config value,
         * and are defined in the Dashboard::path('routes/dashboard.php') file.
         */
        Route::domain((string) config('settings.domain'))
            ->prefix(Dashboard::prefix('/'))
            ->as('settings.')
            ->middleware(config('settings.middleware.private'))
            ->group(Dashboard::path('routes/dashboard.php'));

        /*
         * Auth routes.
         *
         * The auth routes have a subdomain of the settings.domain config value,
         * a prefix consisting of the Dashboard::prefix() method return value,
         * an alias of 'settings.', middleware from the settings.middleware.public config value,
         * and are defined in the Dashboard::path('routes/auth.php') file.
         */
        Route::domain((string) config('settings.domain'))
            ->prefix(Dashboard::prefix('/'))
            ->as('settings.')
            ->middleware(config('settings.middleware.public'))
            ->group(Dashboard::path('routes/auth.php'));

        /*
         * Application routes.
         *
         * If the 'routes/settings.php' file exists, its routes have a subdomain of the settings.domain config value,
         * a prefix consisting of the Dashboard::prefix() method return value,
         * and middleware from the settings.middleware.private config value.
         */
        if (file_exists(base_path('routes/settings.php'))) {
            Route::domain((string) config('settings.domain'))
                ->prefix(Dashboard::prefix('/'))
                ->middleware(config('settings.middleware.private'))
                ->group(base_path('routes/settings.php'));
        }
    }
}
