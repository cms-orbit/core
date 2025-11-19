<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use CmsOrbit\Core\Settings\Http\Controllers\AsyncController;
use CmsOrbit\Core\Settings\Http\Controllers\AttachmentController;
use CmsOrbit\Core\Settings\Http\Controllers\IndexController;
use CmsOrbit\Core\Settings\Http\Controllers\RelationController;
use CmsOrbit\Core\Settings\Http\Controllers\SortableController;
use CmsOrbit\Core\Settings\Http\Screens\NotificationScreen;
use Tabuna\Breadcrumbs\Trail;

// Index and default...
Route::get('/', [IndexController::class, 'index'])
    ->name('index')
    ->breadcrumbs(fn (Trail $trail) => $trail->push(__('Home'), route('settings.index')));

Route::post('search/{query}', [\CmsOrbit\Core\Settings\Http\Controllers\SearchController::class, 'search'])
    ->where('query', '.*')
    ->name('search');

Route::post('async', [AsyncController::class, 'load'])
    ->name('async');

Route::post('listener/{screen}/{layout}', [AsyncController::class, 'listener'])
    ->name('async.listener');

// TODO: Remove group
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

if (config('settings.notifications.enabled', true)) {
    Route::screen('notifications/{id?}', NotificationScreen::class)
        ->name('notifications')
        ->breadcrumbs(fn (Trail $trail) => $trail->parent('settings.index')
            ->push(__('Notifications')));

    Route::post('api/notifications', [NotificationScreen::class, 'unreadNotification'])
        ->name('api.notifications');
}

if (config('settings.fallback', true)) {
    Route::fallback([IndexController::class, 'fallback']);
}
