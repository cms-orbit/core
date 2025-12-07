<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use CmsOrbit\Core\Http\Controllers\AsyncController;
use CmsOrbit\Core\Http\Controllers\AttachmentController;
use CmsOrbit\Core\Http\Controllers\IndexController;
use CmsOrbit\Core\Http\Controllers\RelationController;
use CmsOrbit\Core\Http\Controllers\SearchController;
use CmsOrbit\Core\Http\Controllers\SortableController;
use CmsOrbit\Core\Http\Screens\NotificationScreen;
use CmsOrbit\Core\Resources\ResourceRequest;
use CmsOrbit\Core\Resources\Screens\CreateScreen;
use CmsOrbit\Core\Resources\Screens\EditScreen;
use CmsOrbit\Core\Resources\Screens\ListScreen;
use CmsOrbit\Core\Resources\Screens\ViewScreen;
use Tabuna\Breadcrumbs\Trail;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
| Note: Authentication routes are registered separately in CoreServiceProvider
| to avoid Access middleware causing infinite redirects.
|--------------------------------------------------------------------------
*/

Route::get('/', [IndexController::class, 'index'])
    ->name('index')
    ->breadcrumbs(fn (Trail $trail) => $trail->push(__('Home'), route('orbit.index')));

Route::post('search/{query}', [SearchController::class, 'search'])
    ->where('query', '.*')
    ->name('search');

Route::post('async', [AsyncController::class, 'load'])
    ->name('async');

Route::post('listener/{screen}/{layout}', [AsyncController::class, 'listener'])
    ->name('async.listener');

/*
|--------------------------------------------------------------------------
| System Routes
|--------------------------------------------------------------------------
*/

Route::prefix('systems')->group(function () {
    Route::post('files', [AttachmentController::class, 'upload'])
        ->name('systems.files.upload');

    Route::post('media', [AttachmentController::class, 'media'])
        ->name('systems.files.media');

    Route::post('files/sort', [AttachmentController::class, 'sort'])
        ->name('systems.files.sort');

    Route::delete('files/{id}', [AttachmentController::class, 'destroy'])
        ->name('systems.files.destroy');

    Route::put('files/post/{id}', [AttachmentController::class, 'update'])
        ->name('systems.files.update');

    Route::post('relation', [RelationController::class, 'view'])
        ->name('systems.relation');

    Route::post('sorting', [SortableController::class, 'saveSortOrder'])
        ->name('systems.sorting');
});

/*
|--------------------------------------------------------------------------
| Notification Routes
|--------------------------------------------------------------------------
*/

if (config('orbit.notifications.enabled', true)) {
    Route::screen('notifications/{id?}', NotificationScreen::class)
        ->name('notifications')
        ->breadcrumbs(fn (Trail $trail) => $trail->parent('orbit.index')
            ->push(__('Notifications')));

    Route::post('api/notifications', [NotificationScreen::class, 'unreadNotification'])
        ->name('api.notifications');
}

/*
|--------------------------------------------------------------------------
| Resource Routes (CRUD)
|--------------------------------------------------------------------------
*/

Route::screen('/resources/create/{resource?}', CreateScreen::class)
    ->name('resource.create')
    ->breadcrumbs(function (Trail $trail) {
        $resource = app(ResourceRequest::class)->resource();

        return $trail
            ->parent('orbit.resource.list')
            ->push($resource::createBreadcrumbsMessage());
    });

Route::screen('/resources/view/{resource?}/{id}', ViewScreen::class)
    ->name('resource.view')
    ->breadcrumbs(function (Trail $trail) {
        $resource = app(ResourceRequest::class)->resource();
        $id = request()->route('id');

        return $trail
            ->parent('orbit.resource.list')
            ->push(request()->route('id'), \route('orbit.resource.view', [$resource::uriKey(), $id]));
    });

Route::screen('/resources/edit/{resource?}/{id}', EditScreen::class)
    ->name('resource.edit')
    ->breadcrumbs(function (Trail $trail, $name, $id) {
        $resource = app(ResourceRequest::class)->resource();

        return $trail
            ->parent('orbit.resource.view', [$name, $id])
            ->push($resource::editBreadcrumbsMessage());
    });

Route::screen('/resources/list/{resource?}', ListScreen::class)
    ->name('resource.list')
    ->breadcrumbs(function (Trail $trail) {
        $resource = app(ResourceRequest::class)->resource();

        return $trail->parent('orbit.index')
            ->push($resource::listBreadcrumbsMessage(), \route('orbit.resource.list', [$resource::uriKey()]));
    });

/*
|--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------
*/

if (config('orbit.fallback', true)) {
    Route::fallback([IndexController::class, 'fallback']);
}

