<?php

namespace CmsOrbit\Core\Entities\User\Layouts;

use CmsOrbit\Core\Entities\User\User;
use CmsOrbit\Core\UI\Actions\Link;
use CmsOrbit\Core\UI\Layouts\Table;
use CmsOrbit\Core\UI\TD;

class UserListLayout extends Table
{
    /**
     * Data source.
     *
     * @var string
     */
    protected $target = 'users';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('no', __('No'))
                ->width('80px')
                ->render(fn (User $user) => $user->getAttribute('no')),

            TD::make('name', __('Name'))
                ->sort()
                ->cantHide()
                ->filter(TD::FILTER_TEXT)
                ->render(fn (User $user) => \Illuminate\Support\Facades\Route::has('orbit.entities.users.edit')
                    ? Link::make($user->name)->route('orbit.entities.users.edit', $user)
                    : $user->name),

            TD::make('email', __('Email'))
                ->sort()
                ->cantHide()
                ->filter(TD::FILTER_TEXT),

            TD::make('roles', __('Roles'))
                ->render(fn (User $user) => $user->roles->pluck('name')->implode(', ')),

            TD::make('created_at', __('Created'))
                ->sort()
                ->render(fn (User $user) => $user->created_at->toDateTimeString()),
        ];
    }
}

