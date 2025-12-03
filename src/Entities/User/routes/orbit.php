<?php

use CmsOrbit\Core\Entities\User\Screens\UserEditScreen;
use CmsOrbit\Core\Entities\User\Screens\UserListScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

// Platform > System > Users > User
Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('entities.users.edit')
    ->breadcrumbs(fn (Trail $trail, $user) => $trail
        ->parent('orbit.entities.users')
        ->push($user->name, route('orbit.entities.users.edit', $user)));

// Platform > System > Users > Create
Route::screen('users/create', UserEditScreen::class)
    ->name('entities.users.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('orbit.entities.users')
        ->push(__('Create'), route('orbit.entities.users.create')));

// Platform > System > Users
Route::screen('users', UserListScreen::class)
    ->name('entities.users')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('orbit.index')
        ->push(__('Users'), route('orbit.entities.users'))
    );

