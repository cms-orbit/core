<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts\Concerns;

use CmsOrbit\Core\Screen\Actions\Menu;
use CmsOrbit\Core\Screen\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Shared serialization for navigation-style layouts (SideMenu / TabMenu).
 *
 * Maps a list of {@see Menu} actions to the `{ label, url, active }` item shape
 * consumed by the React SideMenuLayout / TabMenuLayout components.
 */
trait SerializesMenu
{
    /**
     * @param  iterable<Menu>  $navigations
     * @return array<int, array{label: mixed, url: string|null, active: bool}>
     */
    protected function serializeNavigations(iterable $navigations, Repository $repository): array
    {
        $this->query = $repository;

        return collect($navigations)
            ->filter(static fn (Menu $menu) => $menu->isSee())
            ->map(function (Menu $menu) {
                $node = $menu->toArray() ?? [];
                $attributes = $node['attributes'] ?? [];

                return [
                    'label' => $attributes['title'] ?? $attributes['name'] ?? ($node['name'] ?? null),
                    'url' => $attributes['href'] ?? null,
                    'active' => $this->isMenuActive($attributes['active'] ?? null),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Resolve whether any of the menu's active patterns matches the current URL.
     *
     * @param  array<int, string>|string|null  $patterns
     */
    protected function isMenuActive(array|string|null $patterns): bool
    {
        if (empty($patterns)) {
            return false;
        }

        $current = request()->fullUrl();
        $path = request()->path();

        return collect(Arr::wrap($patterns))->contains(function ($pattern) use ($current, $path) {
            $pattern = (string) $pattern;

            return Str::is($pattern, $current) || Str::is(ltrim($pattern, '/'), $path);
        });
    }
}
