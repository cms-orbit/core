<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Config\Screens;

use CmsOrbit\Core\Config\ConfigGroup;
use CmsOrbit\Core\Config\ConfigRegistry;
use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Screen;
use CmsOrbit\Core\Support\Facades\Layout as LayoutFactory;
use Illuminate\Support\Facades\Auth;

/**
 * Settings hub: renders a card grid of every registered config group the
 * current user may access.
 */
class SettingsScreen extends Screen
{
    public function name(): ?string
    {
        return __('Settings');
    }

    public function permission(): ?iterable
    {
        return ['orbit.configs'];
    }

    /**
     * Keep the settings hub inside the standard Orbit shell while inheriting
     * the shared admin content width.
     *
     * @return array{contentWidth: null}
     */
    public function shell(): array
    {
        return [
            'contentWidth' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        $registry = app(ConfigRegistry::class);
        $user = Auth::user();

        $groups = collect($registry->getGroups())
            ->filter(fn (ConfigGroup $group) => $group->isAccessibleBy($user))
            ->map(fn (ConfigGroup $group) => [
                'title'       => $group->getTitle(),
                'description' => $group->getDescription(),
                'icon'        => $group->getIcon(),
                'uriKey'      => $group->getUriKey(),
                'url'         => route('orbit.configs.group', ['group' => $group->getUriKey()]),
            ])
            ->values()
            ->all();

        return [
            'groups' => $groups,
        ];
    }

    /**
     * @return Layout[]
     */
    public function layout(): array
    {
        // Auto-rendered by the React "settings-hub" component, fed the "groups"
        // prop from query(). See CONTRACT.md.
        return [
            LayoutFactory::component('settings-hub'),
        ];
    }
}
