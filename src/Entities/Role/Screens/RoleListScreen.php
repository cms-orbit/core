<?php

namespace CmsOrbit\Core\Entities\Role\Screens;

use CmsOrbit\Core\Auth\Models\Role;
use CmsOrbit\Core\Entities\Role\Layouts\RoleListLayout;
use CmsOrbit\Core\UI\Actions\Link;
use CmsOrbit\Core\UI\Screen;
use Illuminate\Support\Facades\Auth;

class RoleListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     */
    public function query(): iterable
    {
        return [
            'roles' => Role::orderBy('id', 'desc')->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return __('Role Management');
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return __('A comprehensive list of all roles and permissions.');
    }

    /**
     * Permission required to access this screen
     */
    public function permission(): ?iterable
    {
        return [
            'orbit.entities.roles',
        ];
    }

    /**
     * The screen's action buttons.
     */
    public function commandBar(): iterable
    {
        $actions = [];
        $user = Auth::user();

        if ($user->hasAccess('orbit.entities.roles.create') && \Illuminate\Support\Facades\Route::has('orbit.entities.roles.create')) {
            $actions[] = Link::make(__('Add'))
                ->icon('bs.plus-circle')
                ->route('orbit.entities.roles.create');
        }

        return $actions;
    }

    /**
     * The screen's layout elements.
     */
    public function layout(): iterable
    {
        return [
            RoleListLayout::class,
        ];
    }
}

