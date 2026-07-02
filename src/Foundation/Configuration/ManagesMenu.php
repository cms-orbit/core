<?php

namespace CmsOrbit\Core\Foundation\Configuration;

use CmsOrbit\Core\Screen\Actions\Menu;
use CmsOrbit\Core\Support\Attributes\FlushOctaneState;
use Illuminate\Support\Facades\Auth;

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
     * Menu sections submitted by Core and satellite packages, keyed by a stable
     * section identifier (see {@see Entity::sectionKey()}).
     *
     * @var array<string, array{icon: string, label: ?string, sort: int}>
     */
    #[FlushOctaneState]
    protected array $menuSections = [];

    /**
     * Register a menu element with the Dashboard.
     *
     * @param  Menu  $menu  The menu element to add.
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
     * Register a menu section for the admin icon rail / section nav. Packages
     * call this from their service provider; entities link items via
     * {@see Entity::sectionKey()}.
     */
    public function registerSection(string $key, string $icon, ?string $label = null, int $sort = 5000): static
    {
        $this->menuSections[$key] = [
            'icon' => $icon,
            'label' => $label,
            'sort' => $sort,
        ];

        return $this;
    }

    /**
     * All registered menu sections keyed by section identifier.
     *
     * @return array<string, array{icon: string, label: ?string, sort: int}>
     */
    public function getSections(): array
    {
        return $this->menuSections;
    }

    /**
     * Render the menu as a string for display.
     *
     *
     * @return string The rendered menu HTML.
     *
     * @throws \Throwable If rendering fails.
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
     * Serialize the registered menu into a tree for the React admin shell.
     * Permission filtering is deferred to request time using the "permission"
     * attribute set at registration (rather than Menu::permission()).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMenu(): array
    {
        return collect($this->menuItems)
            ->sort(fn (Menu $a, Menu $b) => $a->get('sort', 0) <=> $b->get('sort', 0))
            ->filter(fn (Menu $menu) => $this->menuItemVisible($menu))
            ->map(fn (Menu $menu) => $this->serializeMenuItem($menu))
            ->values()
            ->all();
    }

    /**
     * Whether a menu item is visible for the current user.
     */
    protected function menuItemVisible(Menu $menu): bool
    {
        $permission = $menu->get('permission');

        if ($permission === null) {
            return $menu->isSee();
        }

        $user = Auth::user();

        if ($user === null || ! method_exists($user, 'hasAnyAccess')) {
            return true;
        }

        return $user->hasAnyAccess($permission);
    }

    /**
     * Serialize a menu item to the shape consumed by the React admin shell
     * (`label`/`url`/`children`/scalar `badge`). See CONTRACT.md → MenuItem.
     *
     * @return array<string, mixed>
     */
    protected function serializeMenuItem(Menu $menu): array
    {
        $children = collect($menu->get('list', []))
            ->filter(fn ($item) => $item instanceof Menu && $this->menuItemVisible($item))
            ->map(fn (Menu $item) => $this->serializeMenuItem($item))
            ->values()
            ->all();

        return [
            'label' => $menu->get('title') ?? $menu->get('name'),
            'icon' => $menu->get('icon'),
            'url' => $menu->get('href'),
            'badge' => $this->serializeMenuBadge($menu),
            'section' => $menu->get('section'),
            'sectionKey' => $menu->get('sectionKey'),
            'sort' => $menu->get('sort', 0),
            'divider' => (bool) $menu->get('divider', false),
            'active' => $menu->get('active'),
            'children' => $children,
        ];
    }

    /**
     * Resolve a menu badge to a scalar value (the React shell renders a simple
     * badge). Closures are evaluated; non-scalar payloads are dropped.
     */
    protected function serializeMenuBadge(Menu $menu): string|int|null
    {
        $badge = $menu->get('badge');

        if (! is_array($badge)) {
            return null;
        }

        $data = $badge['data'] ?? null;

        if ($data instanceof \Closure) {
            $data = $data();
        }

        return is_scalar($data) ? $data : null;
    }

    /**
     * Add submenu items to a menu element identified by its slug.
     *
     * @param  string  $slug  The slug of the menu element to update.
     * @param  Menu[]  $list  Array of submenu items to add.
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
