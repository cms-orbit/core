<?php

namespace CmsOrbit\Core\Entities\User\Screens;

use CmsOrbit\Core\Entities\User\Layouts\UserListLayout;
use CmsOrbit\Core\Entities\User\User;
use CmsOrbit\Core\UI\Actions\Link;
use CmsOrbit\Core\UI\Screen;
use Illuminate\Support\Facades\Auth;

class UserListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     */
    public function query(): iterable
    {
        $collection = User::with('roles')
            ->orderBy('id', 'desc')
            ->paginate();

        $startNo = $collection->total() - ($collection->perPage() * ($collection->currentPage() - 1));

        return [
            'users' => $collection->through(function (User $user, $key) use ($startNo) {
                return $user->setAttribute('no', $startNo - $key);
            }),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return __('User Management');
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return __('A comprehensive list of all registered users, including their profiles and privileges.');
    }

    /**
     * Permission required to access this screen
     */
    public function permission(): ?iterable
    {
        return [
            'orbit.entities.users',
        ];
    }

    /**
     * The screen's action buttons.
     */
    public function commandBar(): iterable
    {
        $actions = [];
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasAccess('orbit.entities.users.create') && \Illuminate\Support\Facades\Route::has('orbit.entities.users.create')) {
            $actions[] = Link::make(__('Add'))
                ->icon('bs.plus-circle')
                ->route('orbit.entities.users.create');
        }

        return $actions;
    }

    /**
     * The screen's layout elements.
     */
    public function layout(): iterable
    {
        return [
            UserListLayout::class,
        ];
    }
}

