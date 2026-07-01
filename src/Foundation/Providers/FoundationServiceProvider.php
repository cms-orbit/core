<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Providers;

use CmsOrbit\Core\Foundation\Orbit;
use CmsOrbit\Core\Support\Facades\Dashboard;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Scout\ScoutServiceProvider;
use Tabuna\Breadcrumbs\BreadcrumbsServiceProvider;
use Watson\Active\ActiveServiceProvider;

/**
 * Class FoundationServiceProvider.
 * After update run: php artisan vendor:publish --provider="CmsOrbit\Core\Foundation\Providers\FoundationServiceProvider".
 */
class FoundationServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this
            ->registerViews()
            ->registerMigrations()
            ->registerOctaneEventsListen();
    }

    /**
     * Load the package migrations so the engine tables are available without an
     * explicit publish step.
     *
     * @return $this
     */
    public function registerMigrations(): self
    {
        $this->loadMigrationsFrom(Orbit::path('database/migrations'));

        return $this;
    }

    /**
     * Register translations.
     *
     * @return $this
     */
    public function registerTranslations(): self
    {
        $this->loadJsonTranslationsFrom(Orbit::path('resources/lang/'));

        return $this;
    }

    /**
     * Register views & Publish views.
     *
     * @return $this
     */
    public function registerViews(): self
    {
        $this->loadViewsFrom(Orbit::path('resources/views'), 'orbit');

        return $this;
    }

    /**
     * Register provider.
     *
     * @return $this
     */
    public function registerProviders(): self
    {
        foreach ($this->provides() as $provide) {
            $this->app->register($provide);
        }

        if ($this->app->runningInConsole()) {
            $this->app->register(ConsoleServiceProvider::class);
        }

        return $this;
    }

    /**
     * Flush state when using Laravel Octane
     * https://laravel.com/docs/8.x/octane
     *
     * @return $this
     */
    public function registerOctaneEventsListen(): self
    {
        Event::listen(
            fn (RequestReceived $request) => Dashboard::flush()
        );

        return $this;
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ScoutServiceProvider::class,
            ActiveServiceProvider::class,
            BreadcrumbsServiceProvider::class,
            // EntityRegistry / ConfigRegistry singletons are bound in these
            // providers' register() (which runs before any boot()), so the
            // route file can consume them. They are listed AFTER the route
            // provider so their app->booted() callbacks (which resolve named
            // routes for menus) run after the router refreshes its name lookup.
            RouteServiceProvider::class,
            EntityServiceProvider::class,
            ConfigServiceProvider::class,
            SeoServiceProvider::class,
            MediaServiceProvider::class,
            PlatformServiceProvider::class,
        ];
    }

    /**
     * Register bindings the service provider.
     */
    public function register(): void
    {
        $this
            ->registerTranslations()
            ->registerProviders();

        $this->app->singleton(
            Orbit::class,
            static fn (Application $app) => new Orbit
        );

        $this
            ->registerScreenMacro()
            ->mergeConfigFrom(
                Orbit::path('config/orbit.php'), 'orbit'
            );
    }

    /**
     * Register the 'screen' route macro.
     */
    protected function registerScreenMacro(): self
    {
        if (Route::hasMacro('screen')) {
            return $this;
        }

        $macro = function (string $url, string $screen) {
            return Route::match(['GET', 'HEAD', 'POST'], $url.'/{method?}', $screen)
                ->where('method', $screen::getAvailableMethods()->implode('|'));
        };

        Route::macro('screen', $macro);

        return $this;
    }
}
