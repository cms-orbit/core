<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Support;

use CmsOrbit\Core\Settings\Dashboard;
use CmsOrbit\Core\UI\Actions\Menu;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Entity Bootstrapper
 * 
 * AppServiceProvider에서 엔티티를 쉽게 등록할 수 있는 헬퍼 클래스
 */
class EntityBootstrapper
{
    protected EntityDiscovery $discovery;
    protected Dashboard $dashboard;

    public function __construct(EntityDiscovery $discovery, Dashboard $dashboard)
    {
        $this->discovery = $discovery;
        $this->dashboard = $dashboard;
    }

    /**
     * 엔티티 경로 등록
     */
    public function registerPath(string $path): static
    {
        $this->discovery->registerPath($path);

        return $this;
    }

    /**
     * 라우트 로드
     */
    public function loadRoutes(): static
    {
        $app = app();
        
        if (!$app->bound('router') || !$app->bound('config')) {
            return $this;
        }

        $router = $app->make('router');
        $domain = (string) config('orbit.domain');
        $prefix = config('orbit.prefix', 'admin');
        $middleware = config('orbit.middleware.private');

        foreach ($this->discovery->getRouteFiles() as $routeFile) {
            $router->domain($domain)
                ->prefix($prefix)
                ->middleware($middleware)
                ->group($routeFile);
        }

        return $this;
    }

    /**
     * 마이그레이션 로드
     */
    public function loadMigrations(): static
    {
        foreach ($this->discovery->getMigrationPaths() as $path) {
            app()->make('migrator')->path($path);
        }

        return $this;
    }

    /**
     * 메뉴 등록
     */
    public function registerMenus(int $baseSort = 1000): static
    {
        // Defer menu registration until routes are loaded
        app()->booted(function () use ($baseSort) {
            $entities = $this->discovery->discover();
            $currentSort = $baseSort;

            foreach ($entities as $entity) {
                $menu = $this->buildEntityMenu($entity, $currentSort);
                $this->dashboard->registerMenuElement($menu);
                $currentSort += 10;
            }
        });

        return $this;
    }

    /**
     * 엔티티 메뉴 구성
     */
    protected function buildEntityMenu(array $entity, int $sort): Menu
    {
        $name = $entity['name'];
        $routeName = Str::snake(Str::plural($name));
        $title = Str::title(Str::replace('_', ' ', $routeName));
        
        $mainRoute = $entity['is_resource'] 
            ? "orbit.{$routeName}.list"
            : "orbit.entities.{$routeName}";

        // Main menu
        $menu = Menu::make($title)
            ->icon('bs.folder')
            ->permission("orbit.entities.{$routeName}")
            ->sort($sort);

        // Set route only if it exists
        $router = app()->bound('router') ? app()->make('router') : null;
        if ($router && $router->has($mainRoute)) {
            $menu->route($mainRoute);
        } else {
            $menu->url('#');
        }

        // Submenu items
        $submenus = [];

        // All items
        $allMenu = Menu::make(__('All :entity', ['entity' => $title]))
            ->icon('bs.list')
            ->permission("orbit.entities.{$routeName}")
            ->sort(10);
            
        if ($router && $router->has($mainRoute)) {
            $allMenu->route($mainRoute);
        } else {
            $allMenu->url('#');
        }
        $submenus[] = $allMenu;

        // New item
        $createRoute = $entity['is_resource'] 
            ? "orbit.{$routeName}.create"
            : "orbit.entities.{$routeName}.create";
            
        $createMenu = Menu::make(__('New :entity', ['entity' => $name]))
            ->icon('bs.plus-circle')
            ->permission("orbit.entities.{$routeName}.create")
            ->sort(20);
            
        if ($router && $router->has($createRoute)) {
            $createMenu->route($createRoute);
        } else {
            $createMenu->url('#');
        }
        $submenus[] = $createMenu;

        // Trash (if SoftDeletes)
        if ($entity['has_soft_deletes']) {
            $trashRoute = $entity['is_resource']
                ? "orbit.{$routeName}.trash"
                : "orbit.entities.{$routeName}.trash";
                
            $trashMenu = Menu::make(__('Trash'))
                ->icon('bs.trash')
                ->permission("orbit.entities.{$routeName}.trash")
                ->sort(30);
                
            if ($router && $router->has($trashRoute)) {
                $trashMenu->route($trashRoute);
            } else {
                $trashMenu->url('#');
            }
            $submenus[] = $trashMenu;
        }

        $menu->list($submenus);

        return $menu;
    }

    /**
     * 모든 기능 한 번에 등록
     */
    public function bootstrap(int $menuSort = 1000): static
    {
        return $this
            ->loadRoutes()
            ->loadMigrations()
            ->registerMenus($menuSort);
    }
}

