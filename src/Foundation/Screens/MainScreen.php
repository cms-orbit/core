<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Screens;

use CmsOrbit\Core\Foundation\Models\Role;
use CmsOrbit\Core\Foundation\Models\User;
use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Screen;
use CmsOrbit\Core\Support\Facades\Layout as LayoutFactory;

/**
 * Default landing screen for the admin panel. The package ships a minimal
 * overview so the dashboard renders out of the box; host applications can swap
 * the `orbit.index` config route to point at their own screen.
 */
class MainScreen extends Screen
{
    public function name(): ?string
    {
        return __('Dashboard');
    }

    public function description(): ?string
    {
        return __('Welcome to the Orbit admin panel.');
    }

    public function permission(): ?iterable
    {
        return ['orbit.index'];
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return [
            'metrics' => [
                'users' => User::query()->count(),
                'roles' => Role::query()->count(),
            ],
        ];
    }

    /**
     * @return Layout[]
     */
    public function layout(): array
    {
        return [
            LayoutFactory::metrics([
                __('Users') => 'metrics.users',
                __('Roles') => 'metrics.roles',
            ])->title(__('Overview')),
        ];
    }
}
