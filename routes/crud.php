<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use CmsOrbit\Core\Crud\ResourceRequest;
use CmsOrbit\Core\Crud\Screens\CreateScreen;
use CmsOrbit\Core\Crud\Screens\ViewScreen;
use CmsOrbit\Core\Crud\Screens\EditScreen;
use CmsOrbit\Core\Crud\Screens\ListScreen;
use Tabuna\Breadcrumbs\Trail;

Route::screen('/crud/create/{resource?}', CreateScreen::class)
    ->name('resource.create')
    ->breadcrumbs(function (Trail $trail) {
        $resource = app(ResourceRequest::class)->resource();

        return $trail
            ->parent('settings.resource.list')
            ->push($resource::createBreadcrumbsMessage());
    });

Route::screen('/crud/view/{resource?}/{id}', ViewScreen::class)
    ->name('resource.view')
    ->breadcrumbs(function (Trail $trail) {
        $resource = app(ResourceRequest::class)->resource();
        $id = request()->route('id');

        return $trail
            ->parent('settings.resource.list')
            ->push(request()->route('id'), \route('settings.resource.view', [$resource::uriKey(), $id]));
    });

Route::screen('/crud/edit/{resource?}/{id}', EditScreen::class)
    ->name('resource.edit')
    ->breadcrumbs(function (Trail $trail, $name, $id) {
        $resource = app(ResourceRequest::class)->resource();

        return $trail
            ->parent('settings.resource.view', [$name, $id])
            ->push($resource::editBreadcrumbsMessage());
    });

Route::screen('/crud/list/{resource?}', ListScreen::class)
    ->name('resource.list')
    ->breadcrumbs(function (Trail $trail) {
        $resource = app(ResourceRequest::class)->resource();

        return $trail->parent('settings.index')
            ->push($resource::listBreadcrumbsMessage(), \route('settings.resource.list', [$resource::uriKey()]));
    });
