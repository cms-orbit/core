<?php

declare(strict_types=1);

namespace CmsOrbit\Core;

use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use CmsOrbit\Core\Icons\IconFinder;

/*
 * This class represents the Orbit Service Provider.
 * It is used to register the menus, permissions and search models to the dashboard.
 */
abstract class OrbitServiceProvider extends ServiceProvider
{
    /**
     * The Orbit Dashboard instance.
     *
     * @var \CmsOrbit\Core\Settings\Dashboard|null
     */
    protected ?Dashboard $orbit;

    /**
     * Boot the application events.
     */
    public function boot(Dashboard $dashboard): void
    {
        // Need for backward compatibility
        $this->orbit = $dashboard;

        $this
            ->definePermissions()
            ->defineRoutes()
            ->defineSearch()
            ->defineIcons()
            ->defineMenu()
            ->defineResources();
    }

    /**
     * Get the Orbit Dashboard instance.
     *
     * @return \CmsOrbit\Core\Settings\Dashboard
     */
    private function orbitSingleton(): Dashboard
    {
        if ($this->orbit === null) {
            $this->orbit = $this->app->make(Dashboard::class);
        }

        return $this->orbit;
    }

    /**
     * Define search functionality for the dashboard.
     *
     * @return $this
     */
    private function defineSearch(): static
    {
        $searchModels = config('settings.search', []);

        if (!empty($searchModels)) {
            $this->orbitSingleton()->registerSearch($searchModels);
        }

        return $this;
    }

    /**
     * Define menu items for the dashboard.
     *
     * @return $this
     */
    private function defineMenu(): static
    {
        // Register the menu items
        View::composer('settings::dashboard', function () {
            $elements = $this->menu();

            foreach ($elements as $element) {
                $this->orbitSingleton()->registerMenuElement($element);
            }
        });

        return $this;
    }

    /**
     * Define permissions for the dashboard.
     *
     * @return $this
     */
    private function definePermissions(): static
    {
        $permissions = $this->permissions();

        // Register the permissions
        foreach ($permissions as $permission) {
            $this->orbitSingleton()->registerPermission($permission);
        }

        return $this;
    }

    /**
     * Define routes for the dashboard.
     *
     * @return $this
     */
    private function defineRoutes(): static
    {
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return $this;
        }

        Route::domain((string) config('settings.domain'))
            ->prefix(Dashboard::prefix('/'))
            ->middleware(config('settings.middleware.private'))
            ->group(function (Router $route) {
                $this->routes($route);
            });

        return $this;
    }

    /**
     * Define icon registration for the dashboard.
     *
     * @return $this
     */
    private function defineIcons(): static
    {
        $iconFinder = $this->app->make(IconFinder::class);

        collect($this->icons())->each(fn ($path, $prefix) => $iconFinder->registerIconDirectory($prefix, $path));

        return $this;
    }

    /**
     * Get the icon paths and prefixes.
     *
     * @return array
     */
    public function icons(): array
    {
        return [];
    }

    /**
     * Returns an array of menu items.
     *
     * @return \CmsOrbit\Core\Screen\Actions\Menu[]
     */
    public function menu(): array
    {
        return [];
    }

    /**
     * Returns an array of permissions.
     *
     * @return ItemPermission[]
     */
    public function permissions(): array
    {
        return [];
    }

    /**
     * Define routes setup.
     *
     * @param \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function routes(Router $router): void
    {
        // Define routes.
    }

    /**
     * Define the stylesheets to be registered.
     *
     * @return string[]
     */
    public function stylesheets(): array
    {
        return [];
    }

    /**
     * Define the scripts to be registered.
     *
     * @return string[]
     */
    public function scripts(): array
    {
        return [];
    }

    /**
     * Define the resources to be registered.
     *
     * @return void
     */
    protected function defineResources(): void
    {
        foreach ($this->stylesheets() as $stylesheet) {
            $this->orbit->registerResource('stylesheets', $stylesheet);
        }

        foreach ($this->scripts() as $script) {
            $this->orbit->registerResource('scripts', $script);
        }
    }
}
