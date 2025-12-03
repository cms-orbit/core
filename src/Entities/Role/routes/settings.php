<?php

use CmsOrbit\Core\Entities\Role\Screens\RoleEditScreen;
use CmsOrbit\Core\Entities\Role\Screens\RoleListScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

// Platform > System > Roles > Role
Route::screen('roles/{role}/edit', RoleEditScreen::class)
    ->name('entities.roles.edit')
    ->breadcrumbs(fn (Trail $trail, $role) => $trail
        ->parent('orbit.entities.roles')
        ->push($role->name, route('orbit.entities.roles.edit', $role)));

// Platform > System > Roles > Create
Route::screen('roles/create', RoleEditScreen::class)
    ->name('entities.roles.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('orbit.entities.roles')
        ->push(__('Create'), route('orbit.entities.roles.create')));

// Platform > System > Roles
Route::screen('roles', RoleListScreen::class)
    ->name('entities.roles')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('orbit.index')
        ->push(__('Roles'), route('orbit.entities.roles')));

