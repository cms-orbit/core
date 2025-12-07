<?php

namespace CmsOrbit\Core\Entities\Role\Layouts;

use CmsOrbit\Core\Auth\Models\Role;
use CmsOrbit\Core\UI\Actions\Link;
use CmsOrbit\Core\UI\Layouts\Table;
use CmsOrbit\Core\UI\TD;

class RoleListLayout extends Table
{
    /**
     * Data source.
     *
     * @var string
     */
    protected $target = 'roles';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('name', __('Name'))
                ->sort()
                ->cantHide()
                ->filter(TD::FILTER_TEXT)
                ->render(fn (Role $role) => \Illuminate\Support\Facades\Route::has('orbit.entities.roles.edit')
                    ? Link::make($role->name)->route('orbit.entities.roles.edit', $role)
                    : $role->name),

            TD::make('slug', __('Slug'))
                ->sort()
                ->cantHide()
                ->filter(TD::FILTER_TEXT),

            TD::make('created_at', __('Created'))
                ->sort()
                ->render(fn (Role $role) => $role->created_at->toDateTimeString()),
        ];
    }
}

