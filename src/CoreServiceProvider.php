<?php

declare(strict_types=1);

namespace CmsOrbit\Core;

use CmsOrbit\Core\Frontend\FrontendHandler;
use CmsOrbit\Core\Frontend\SitemapGenerator;
use CmsOrbit\Core\Http\Controllers\SitemapController;
use CmsOrbit\Core\Http\Middleware\Access;
use CmsOrbit\Core\Http\Middleware\BladeIcons;
use CmsOrbit\Core\Http\Middleware\Turbo;
use CmsOrbit\Core\Support\EntityBootstrapper;
use CmsOrbit\Core\Support\EntityDiscovery;
use CmsOrbit\Core\Support\PackageManager;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\AboutCommand;
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
        $this->registerMacros()
            ->bootSettings()
            ->bootResources()
            ->bootIcons()
            ->bootCoreEntities()
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
        $this->registerConfig()
            ->registerProviders()
            ->registerSingletons()
            ->registerCommands();
    }

    /**
     * Boot settings dashboard functionality.
     */
    protected function bootSettings(): static
    {
        // Register components only if Blade facade is available
        if ($this->app->bound('blade.compiler')) {
            $blade = $this->app->make('blade.compiler');
            $blade->component('orbit-notification', Settings\Components\Notification::class);
            $blade->component('orbit-stream', Settings\Components\Stream::class);
            $blade->component('orbit-popover', UI\Components\Popover::class);
            $blade->component('orbit-icon', UI\Components\Icon::class);
        }

        // Register menu
        if ($this->app->bound('view')) {
            $view = $this->app->make('view');
            $view->composer('orbit::dashboard', function () {
                // Menu registration will be handled by user's provider
            });
        }

        // Register dashboard resources and permissions (from SettingsServiceProvider)
        if ($this->app->bound('config') && !$this->isPackageDiscovery()) {
            $this->app->booted(function () {
                /** @var Settings\Dashboard $dashboard */
                $dashboard = $this->app->make(Settings\Dashboard::class);
                $dashboard
                    ->registerResource('stylesheets', config('orbit.resource.stylesheets'))
                    ->registerResource('scripts', config('orbit.resource.scripts'))
                    ->registerSearch(config('orbit.search', []))
                    ->registerPermission($this->registerPermissionsMain())
                    ->registerPermission($this->registerPermissionsSystems());
            });
        }

        return $this;
    }

    /**
     * Register main permissions (from SettingsServiceProvider).
     */
    protected function registerPermissionsMain(): Settings\ItemPermission
    {
        return Settings\ItemPermission::group(__('Main'))
            ->addPermission('orbit.index', __('Main'));
    }

    /**
     * Register system permissions (from SettingsServiceProvider).
     */
    protected function registerPermissionsSystems(): Settings\ItemPermission
    {
        return Settings\ItemPermission::group(__('System'))
            ->addPermission('orbit.systems.attachment', __('Attachment'));
    }

    /**
     * Boot resource functionality.
     */
    protected function bootResources(): static
    {
        if (!$this->app->bound('config') || !function_exists('app_path')) {
            return $this;
        }

        try {
            $finder = $this->app->make(Resources\ResourceFinder::class);
            $arbitrator = $this->app->make(Resources\Arbitrator::class);

            $resources = $finder
                ->setNamespace(app()->getNamespace() . 'Orbit\\Resources')
                ->find(app_path('Orbit/Resources'));

            $arbitrator->resources($resources)->boot();
        } catch (\Throwable $e) {
            // Silently fail during package discovery
            if (!$this->app->runningInConsole() || !$this->isPackageDiscovery()) {
                throw $e;
            }
        }

        return $this;
    }

    /**
     * Boot icon functionality.
     */
    protected function bootIcons(): static
    {
        if (!$this->app->bound('config')) {
            return $this;
        }

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
        if (!$this->app->bound('router') || !$this->app->bound('config')) {
            return $this;
        }

        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return $this;
        }

        // Register middleware group (from RouteServiceProvider)
        Route::middlewareGroup('settings', [
            Turbo::class,
            BladeIcons::class,
            Access::class,
        ]);

        $router = $this->app->make('router');
        $domain = (string) config('orbit.domain');
        $prefix = $this->getPrefix();
        $middleware = config('orbit.middleware.private');

        // Core routes (includes auth and dashboard routes)
        $router->domain($domain)
            ->prefix($prefix)
            ->as('orbit.')
            ->middleware($middleware)
            ->group(__DIR__ . '/../routes/routes.php');

        // Application routes (from RouteServiceProvider)
        if (file_exists(base_path('routes/orbit.php'))) {
            Route::domain($domain)
                ->prefix(Settings\Support\Facades\Dashboard::prefix('/'))
                ->middleware(config('orbit.middleware.private'))
                ->group(base_path('routes/orbit.php'));
        }

        // Load entity routes
        /** @var Support\EntityDiscovery $discovery */
        $discovery = $this->app->make(Support\EntityDiscovery::class);

        foreach ($discovery->getRouteFiles() as $routeFile) {
            $router->domain($domain)
                ->prefix($prefix)
                ->middleware($middleware)
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

        // Register AboutCommand info (from ConsoleServiceProvider)
        if (class_exists(AboutCommand::class) && $this->app->bound('config')) {
            try {
                AboutCommand::add('Orbit Settings', fn () => [
                    'Version'       => Support\Facades\Dashboard::version(),
                    'Domain'        => config('orbit.domain'),
                    'Prefix'        => config('orbit.prefix'),
                    'Assets Status' => Support\Facades\Dashboard::assetsAreCurrent() ? '<fg=green;options=bold>CURRENT</>' : '<fg=yellow;options=bold>OUTDATED</>',
                ]);
            } catch (\Throwable $e) {
                // Silently fail if Dashboard facade is not available
            }
        }

        // Core publishing
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

        // Additional publishing for stubs
        $packagePath = $this->getPath('');
        
        $this->publishes([
            $packagePath . '/stubs/app/routes/' => base_path('routes'),
            $packagePath . '/stubs/app/Orbit/' => app_path('Orbit'),
        ], 'orbit-stubs');

        return $this;
    }

    /**
     * Boot Octane support.
     */
    protected function bootOctane(): static
    {
        if ($this->app->bound('events')) {
            $events = $this->app->make('events');
            $events->listen(function (\Laravel\Octane\Events\RequestReceived $request) {
                $this->app->make(Settings\Dashboard::class)->flush();
                $this->app->make(Support\PackageManager::class)->flush();
            });
        }

        return $this;
    }

    /**
     * Register configuration.
     */
    protected function registerConfig(): static
    {
        if ($this->app->bound('config')) {
            $this->mergeConfigFrom($this->getPath('config/orbit.php'), 'orbit');
        }

        return $this;
    }

    /**
     * Register service providers.
     */
    protected function registerProviders(): static
    {
        // Only register providers if config service is available
        if (!$this->app->bound('config')) {
            return $this;
        }

        // Skip during package discovery
        if ($this->isPackageDiscovery()) {
            return $this;
        }

        // Register third-party providers directly (no longer using separate providers)
        $providers = [
            ScoutServiceProvider::class,
            ActiveServiceProvider::class,
            BreadcrumbsServiceProvider::class,
        ];

        foreach ($providers as $provider) {
            $this->app->register($provider);
        }

        // Register user's custom provider (from SettingsServiceProvider)
        // Skip during package discovery
        if ($this->isPackageDiscovery()) {
            return $this;
        }

        try {
            if (!$this->app->bound('config')) {
                return $this;
            }

            // Try orbit.provider first, then fallback to default
            $customProvider = config('orbit.provider', \App\CmsOrbit\Core\SettingsProvider::class);
            if ($customProvider && class_exists($customProvider)) {
                $this->app->register($customProvider);
            }
        } catch (\Throwable $e) {
            // Silently fail if config is not available
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

        // Core commands
        $this->commands([
            Commands\InstallCommand::class,
            Commands\PublishCommand::class,
            Commands\AdminCommand::class,

            Commands\ResourceCommand::class,
            Commands\ActionCommand::class,
            Commands\EntityCommand::class,
            Commands\DocumentCommand::class,
            Commands\ModelCommand::class,
            Commands\MigrationCommand::class,
            Commands\FreshSuperAdminRoleCommand::class,
            Commands\GenerateViteConfigCommand::class,
            Commands\EntityDiscoverCommand::class,

            Commands\FilterCommand::class,
            Commands\RowsCommand::class,
            Commands\ScreenCommand::class,
            Commands\TableCommand::class,
            Commands\ChartCommand::class,
            Commands\SelectionCommand::class,
            Commands\ListenerCommand::class,
            Commands\PresenterCommand::class,
            Commands\TabMenuCommand::class,
            Commands\StubPublishCommand::class,
            Commands\FieldCommand::class,
        ]);

        return $this;
    }

    /**
     * Register route macros.
     */
    protected function registerMacros(): static
    {
        if (!$this->app->bound('router')) {
            return $this;
        }

        $router = $this->app['router'];

        if ($router->hasMacro('screen')) {
            return $this;
        }

        $router->macro('screen', function (string $url, string $screen) {
            return $this->match(['GET', 'HEAD', 'POST'], $url.'/{method?}', $screen)
                ->where('method', $screen::getAvailableMethods()->implode('|'));
        });

        return $this;
    }

    /**
     * Boot core entities (User, Role) if not deployed to root project
     */
    protected function bootCoreEntities(): static
    {
        if (!$this->app->bound(Support\EntityBootstrapper::class) || !function_exists('app_path')) {
            return $this;
        }

        try {
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
        } catch (\Throwable $e) {
            // Silently fail during package discovery
            if (!$this->app->runningInConsole() || !$this->isPackageDiscovery()) {
                throw $e;
            }
        }

        return $this;
    }

    /**
     * Get the route prefix.
     */
    protected function getPrefix(): string
    {
        if (!$this->app->bound('config')) {
            return '/admin';
        }

        return config('orbit.prefix', '/admin');
    }

    /**
     * Get the package path.
     */
    protected function getPath(string $path = ''): string
    {
        return dirname(__DIR__) . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Check if we're running package discovery.
     */
    protected function isPackageDiscovery(): bool
    {
        return isset($_SERVER['argv']) && in_array('package:discover', $_SERVER['argv'], true);
    }
}

