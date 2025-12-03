<?php

declare(strict_types=1);

namespace CmsOrbit\Core;

use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\ScoutServiceProvider;
use Tabuna\Breadcrumbs\BreadcrumbsServiceProvider;
use Watson\Active\ActiveServiceProvider;

/**
 * Core Service Provider for CMS Orbit
 *
 * This is the main service provider that bootstraps all functionality
 * including settings dashboard, resources, icons, and UI components.
 */
class CoreServiceProvider extends ServiceProvider
{
    /**
     * Boot the application services.
     */
    public function boot(): void
    {
        $this
            ->bootSettings()
            ->bootResources()
            ->bootIcons()
            ->bootRoutes()
            ->bootViews()
            ->bootTranslations()
            ->bootPublishing()
            ->bootOctane();
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this
            ->registerConfig()
            ->registerProviders()
            ->registerSingletons()
            ->registerCommands()
            ->registerMacros();
    }

    /**
     * Boot settings dashboard functionality.
     */
    protected function bootSettings(): static
    {
        // Register components
        Blade::component('orbit-notification', Settings\Components\Notification::class);
        Blade::component('orbit-stream', Settings\Components\Stream::class);
        Blade::component('orbit-popover', UI\Components\Popover::class);

        // Register menu
        View::composer('orbit::dashboard', function () {
            // Menu registration will be handled by user's provider
        });

        return $this;
    }

    /**
     * Boot resource functionality.
     */
    protected function bootResources(): static
    {
        $finder = $this->app->make(Resources\ResourceFinder::class);
        $arbitrator = $this->app->make(Resources\Arbitrator::class);

        $resources = $finder
            ->setNamespace(app()->getNamespace() . 'Orbit\\Resources')
            ->find(app_path('Orbit/Resources'));

        $arbitrator->resources($resources)->boot();

        return $this;
    }

    /**
     * Boot icon functionality.
     */
    protected function bootIcons(): static
    {
        $iconFinder = $this->app->make(Foundation\Icons\IconFinder::class);

        foreach (config('orbit.icons', []) as $key => $path) {
            $iconFinder->registerIconDirectory($key, is_callable($path) ? $path() : $path);
        }

        return $this;
    }

    /**
     * Boot routes.
     */
    protected function bootRoutes(): static
    {
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return $this;
        }

        Route::domain((string) config('orbit.domain'))
            ->prefix($this->getPrefix())
            ->as('orbit.')
            ->middleware(config('orbit.middleware.private'))
            ->group(__DIR__ . '/../routes/routes.php');

        return $this;
    }

    /**
     * Boot views.
     */
    protected function bootViews(): static
    {
        $this->loadViewsFrom($this->getPath('resources/views'), 'orbit');

        return $this;
    }

    /**
     * Boot translations.
     */
    protected function bootTranslations(): static
    {
        $this->loadJsonTranslationsFrom($this->getPath('resources/lang/'));

        return $this;
    }

    /**
     * Boot publishing.
     */
    protected function bootPublishing(): static
    {
        if (!$this->app->runningInConsole()) {
            return $this;
        }

        $this->publishes([
            $this->getPath('config/orbit.php') => config_path('orbit.php'),
        ], 'orbit-config');

        $this->publishes([
            $this->getPath('database/migrations') => database_path('migrations'),
        ], 'orbit-migrations');

        $this->publishes([
            $this->getPath('resources/views') => resource_path('views/vendor/orbit'),
        ], 'orbit-views');

        $this->publishes([
            $this->getPath('resources/lang') => lang_path('vendor/orbit'),
        ], 'orbit-lang');

        $this->publishes([
            $this->getPath('public') => public_path('vendor/orbit'),
        ], 'orbit-assets');

        return $this;
    }

    /**
     * Boot Octane support.
     */
    protected function bootOctane(): static
    {
        Event::listen(fn (\Laravel\Octane\Events\RequestReceived $request) =>
            $this->app->make(Settings\Dashboard::class)->flush()
        );

        return $this;
    }

    /**
     * Register configuration.
     */
    protected function registerConfig(): static
    {
        $this->mergeConfigFrom($this->getPath('config/orbit.php'), 'orbit');

        return $this;
    }

    /**
     * Register service providers.
     */
    protected function registerProviders(): static
    {
        $providers = [
            ScoutServiceProvider::class,
            ActiveServiceProvider::class,
            BreadcrumbsServiceProvider::class,
            Settings\Providers\RouteServiceProvider::class,
        ];

        foreach ($providers as $provider) {
            $this->app->register($provider);
        }

        if ($this->app->runningInConsole()) {
            $this->app->register(Settings\Providers\ConsoleServiceProvider::class);
        }

        // Register user's custom provider
        $customProvider = config('orbit.provider');
        if ($customProvider && class_exists($customProvider)) {
            $this->app->register($customProvider);
        }

        return $this;
    }

    /**
     * Register singletons.
     */
    protected function registerSingletons(): static
    {
        $this->app->singleton(Settings\Dashboard::class, fn (Application $app) => new Settings\Dashboard);
        $this->app->singleton(Resources\Arbitrator::class, fn () => new Resources\Arbitrator);
        $this->app->singleton(Foundation\Icons\IconFinder::class, fn () => new Foundation\Icons\IconFinder);

        return $this;
    }

    /**
     * Register commands.
     */
    protected function registerCommands(): static
    {
        if (!$this->app->runningInConsole()) {
            return $this;
        }

        $this->commands([
            Commands\ResourceCommand::class,
            Commands\ActionCommand::class,
        ]);

        return $this;
    }

    /**
     * Register route macros.
     */
    protected function registerMacros(): static
    {
        if (Route::hasMacro('screen')) {
            return $this;
        }

        Route::macro('screen', function (string $url, string $screen) {
            return Route::match(['GET', 'HEAD', 'POST'], $url.'/{method?}', $screen)
                ->where('method', $screen::getAvailableMethods()->implode('|'));
        });

        return $this;
    }

    /**
     * Get the route prefix.
     */
    protected function getPrefix(): string
    {
        return config('orbit.prefix', '/admin');
    }

    /**
     * Get the package path.
     */
    protected function getPath(string $path = ''): string
    {
        return dirname(__DIR__) . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

