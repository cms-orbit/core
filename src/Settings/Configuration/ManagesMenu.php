<?php

namespace CmsOrbit\Core\Settings\Configuration;

use CmsOrbit\Core\UI\Actions\Menu;
use CmsOrbit\Core\Support\Attributes\FlushOctaneState;

trait ManagesMenu
{
    /**
     * The collection of menu items.
     *
     * @var array<Menu>
     */
    #[FlushOctaneState]
    protected array $menuItems = [];

    /**
     * Register a menu element with the Dashboard.
     *
     * @param Menu $menu The menu element to add.
     *
     * @return $this
     */
    public function registerMenuElement(Menu $menu): static
    {
        if ($menu->get('sort', 0) === 0) {
            $menu->sort(count($this->menuItems) + 1);
        }

        $this->menuItems[] = $menu;

        return $this;
    }

    /**
     * Register a menu from array configuration.
     *
     * @param string $route The route name or identifier.
     * @param array $config The menu configuration.
     *
     * @return $this
     */
    public function registerMenu(string $route, array $config): static
    {
        $menu = Menu::make($config['label'] ?? $route)
            ->route($route)
            ->icon($config['icon'] ?? 'bs.circle')
            ->sort($config['sort'] ?? 1000);

        if (isset($config['permission'])) {
            $menu->permission($config['permission']);
        }

        // Add children if exists
        if (!empty($config['children'])) {
            $childMenus = [];
            foreach ($config['children'] as $child) {
                $childMenu = Menu::make($child['label'])
                    ->route($child['route'])
                    ->icon($child['icon'] ?? 'bs.circle')
                    ->sort($child['sort'] ?? 0);

                if (isset($child['permission'])) {
                    $childMenu->permission($child['permission']);
                }

                $childMenus[] = $childMenu;
            }
            $menu->list($childMenus);
        }

        return $this->registerMenuElement($menu);
    }

    /**
     * Render the menu as a string for display.
     *
     * @throws \Throwable If rendering fails.
     *
     * @return string The rendered menu HTML.
     */
    public function renderMenu(): string
    {
        return collect($this->menuItems)
            ->sort(fn (Menu $current, Menu $next) => $current->get('sort', 0) <=> $next->get('sort', 0))
            ->map(fn (Menu $menu) => (string) $menu->render())
            ->implode('');
    }

    /**
     * Check if the menu is empty.
     *
     * @return bool True if the menu is empty, otherwise false.
     */
    public function isEmptyMenu(): bool
    {
        return empty($this->menuItems);
    }

    /**
     * Add submenu items to a menu element identified by its slug.
     *
     * @param string $slug The slug of the menu element to update.
     * @param Menu[] $list Array of submenu items to add.
     *
     * @return $this
     */
    public function addMenuSubElements(string $slug, array $list): static
    {
        $this->menuItems = collect($this->menuItems)
            ->map(fn (Menu $menu) => $slug === $menu->get('slug')
                ? $menu->list($list)
                : $menu)
            ->all();

        return $this;
    }
}
