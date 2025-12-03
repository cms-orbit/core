<?php

declare(strict_types=1);

namespace CmsOrbit\Core;

use CmsOrbit\Core\Frontend\FrontendHandler;
use CmsOrbit\Core\Frontend\SitemapGenerator;
use CmsOrbit\Core\Http\Controllers\SitemapController;
use CmsOrbit\Core\Support\EntityBootstrapper;
use CmsOrbit\Core\Support\EntityDiscovery;
use CmsOrbit\Core\Support\PackageManager;
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
            ->bootCoreEntities()  // Register entities before routes
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
        Blade::component('orbit-icon', UI\Components\Icon::class);

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
     * Boot entities.
     */
    protected function bootEntities(): static
    {
        /** @var Support\EntityDiscovery $discovery */
        $discovery = $this->app->make(Support\EntityDiscovery::class);

        /** @var Support\PackageManager $packageManager */
        $packageManager = $this->app->make(Support\PackageManager::class);

        // Register default entity path (app/Entities)
        $packageManager->registerEntityPath(app_path('Entities'));
        $discovery->registerPath(app_path('Entities'));

        // Register entity paths from packages
        foreach ($packageManager->getEntityPaths() as $path) {
            $discovery->registerPath($path);
        }

        // Discover all entities
        $entities = $discovery->discover();

        // Register entity migrations
        foreach ($discovery->getMigrationPaths() as $path) {
            $this->loadMigrationsFrom($path);
        }

        // Register entity menus
        $this->registerEntityMenus($entities);

        return $this;
    }

    /**
     * Register entity menus.
     */
    protected function registerEntityMenus($entities): void
    {
        /** @var Settings\Dashboard $dashboard */
        $dashboard = $this->app->make(Settings\Dashboard::class);

        foreach ($entities as $entity) {
            $menu = $this->buildEntityMenu($entity);
            $dashboard->registerMenu($menu['route'], $menu);
        }
    }

    /**
     * Build entity menu structure.
     */
    protected function buildEntityMenu(array $entity): array
    {
        $name = $entity['name'];
        $routeName = \Illuminate\Support\Str::snake(\Illuminate\Support\Str::plural($name));
        $title = \Illuminate\Support\Str::title(\Illuminate\Support\Str::replace('_', ' ', $routeName));

        $mainRoute = $entity['is_resource']
            ? "orbit.{$routeName}.list"
            : "orbit.entities.{$routeName}";

        // Build submenu items
        $submenus = [
            [
                'route' => $mainRoute,
                'label' => __('All :entity', ['entity' => $title]),
                'icon' => 'bs.list',
                'permission' => "orbit.entities.{$routeName}",
                'sort' => 10,
            ],
            [
                'route' => $entity['is_resource']
                    ? "orbit.{$routeName}.create"
                    : "orbit.entities.{$routeName}.create",
                'label' => __('New :entity', ['entity' => $name]),
                'icon' => 'bs.plus-circle',
                'permission' => "orbit.entities.{$routeName}.create",
                'sort' => 20,
            ],
        ];

        // Add trash menu if SoftDeletes is enabled
        if ($entity['has_soft_deletes']) {
            $submenus[] = [
                'route' => $entity['is_resource']
                    ? "orbit.{$routeName}.trash"
                    : "orbit.entities.{$routeName}.trash",
                'label' => __('Trash'),
                'icon' => 'bs.trash',
                'permission' => "orbit.entities.{$routeName}.trash",
                'sort' => 30,
            ];
        }

        return [
            'route' => $mainRoute,
            'label' => $title,
            'icon' => 'bs.folder',
            'permission' => "orbit.entities.{$routeName}",
            'sort' => 1000,
            'children' => $submenus,
        ];
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

        // Load entity routes
        /** @var Support\EntityDiscovery $discovery */
        $discovery = $this->app->make(Support\EntityDiscovery::class);

        foreach ($discovery->getRouteFiles() as $routeFile) {
            Route::domain((string) config('orbit.domain'))
                ->prefix($this->getPrefix())
                ->middleware(config('orbit.middleware.private'))
                ->group($routeFile);
        }

        // Sitemap route
        // Route::get('sitemap.xml', Http\Controllers\SitemapController::class)->name('sitemap');

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

        $this->loadMigrationsFrom($this->getPath('database/migrations'));

        $this->publishes([
            $this->getPath('resources/js') => base_path('node_modules/@cms-orbit/core'),
        ], 'orbit-js');

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
        Event::listen(function (\Laravel\Octane\Events\RequestReceived $request) {
            $this->app->make(Settings\Dashboard::class)->flush();
            $this->app->make(Support\PackageManager::class)->flush();
        });

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
        $this->app->singleton(PackageManager::class, fn () => new PackageManager);
        $this->app->singleton(FrontendHandler::class, fn () => new FrontendHandler);
        $this->app->singleton(SitemapGenerator::class, fn () => new SitemapGenerator);
        $this->app->singleton(EntityDiscovery::class, fn () => new EntityDiscovery);
        $this->app->singleton(EntityBootstrapper::class, fn (Application $app) => new EntityBootstrapper(
            $app->make(EntityDiscovery::class),
            $app->make(Settings\Dashboard::class)
        ));

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
            Commands\EntityCommand::class,
            Commands\DocumentCommand::class,
            Commands\ModelCommand::class,
            Commands\MigrationCommand::class,
            Commands\FreshSuperAdminRoleCommand::class,
            Commands\GenerateViteConfigCommand::class,
            Commands\EntityDiscoverCommand::class,
            Commands\InstallCommand::class,
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
     * Boot core entities (User, Role) if not deployed to root project
     */
    protected function bootCoreEntities(): static
    {
        /** @var Support\EntityBootstrapper $entities */
        $entities = $this->app->make(Support\EntityBootstrapper::class);

        // Check if User and Role entities exist in root project
        $rootUserPath = app_path('Entities/User');
        $rootRolePath = app_path('Entities/Role');

        // If entities are deployed to root, use those
        if (is_dir($rootUserPath) && is_dir($rootRolePath)) {
            $entities->registerPath(app_path('Entities'));
        } else {
            // Otherwise, use package entities
            $entities->registerPath($this->getPath('src/Entities'));
        }

        // Load routes and migrations immediately
        $entities->loadRoutes()->loadMigrations();

        // Register menus after routes are loaded (deferred)
        $entities->registerMenus(2000);

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

