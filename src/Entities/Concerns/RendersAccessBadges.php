<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities\Concerns;

use Illuminate\Support\Collection;

/**
 * Small HTML badge helpers shared by the built-in access entities and reused by
 * their {@see \CmsOrbit\Core\Screen\Sight} render closures on the view screen.
 * The markup is injected verbatim by the React legend layout, so the Tailwind
 * classes below are scanned from `packages/.../src` (see resources/css/app.css).
 */
trait RendersAccessBadges
{
    /**
     * Render a pill list from a set of labels, or a muted placeholder.
     *
     * @param  array<int, string>  $items
     */
    protected function badgeList(array $items, string $empty): string
    {
        if ($items === []) {
            return '<span class="text-sm text-gray-400">'.e($empty).'</span>';
        }

        return Collection::make($items)
            ->map(fn (string $item): string => '<span class="mr-1 mb-1 inline-flex items-center rounded-full bg-orbit-primary/10 px-2 py-0.5 text-xs font-medium text-orbit-primary">'.e($item).'</span>')
            ->implode('');
    }

    /**
     * Render a green/grey status pill.
     */
    protected function statusBadge(bool $on, string $onLabel, string $offLabel): string
    {
        return $on
            ? '<span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">'.e($onLabel).'</span>'
            : '<span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">'.e($offLabel).'</span>';
    }
}
