<?php

declare(strict_types=1);

use CmsOrbit\Core\Config\Screens\ConfigGroupScreen;
use CmsOrbit\Core\Config\Screens\SettingsScreen;
use CmsOrbit\Core\Crud\Screens\CreateScreen;
use CmsOrbit\Core\Crud\Screens\EditScreen;
use CmsOrbit\Core\Crud\Screens\ListScreen;
use CmsOrbit\Core\Crud\Screens\TrashScreen;
use CmsOrbit\Core\Crud\Screens\ViewScreen;
use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Foundation\Entity\EntityRegistry;
use CmsOrbit\Core\Foundation\Http\Controllers\AsyncController;
use CmsOrbit\Core\Foundation\Screens\MainScreen;
use CmsOrbit\Core\Foundation\Http\Controllers\AttachmentController;
use CmsOrbit\Core\Foundation\Http\Controllers\ChoicesController;
use CmsOrbit\Core\Foundation\Http\Controllers\IndexController;
use CmsOrbit\Core\Foundation\Http\Controllers\MediaController;
use CmsOrbit\Core\Foundation\Http\Controllers\NotificationController;
use CmsOrbit\Core\Foundation\Http\Controllers\SearchController;
use CmsOrbit\Core\Foundation\Http\Controllers\SitemapController;
use CmsOrbit\Core\Foundation\Http\Controllers\SortableController;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

/*
|--------------------------------------------------------------------------
| Main Entry Point & Dashboard
|--------------------------------------------------------------------------
*/
Route::get('/', [IndexController::class, 'index'])
    ->name('index')
    ->breadcrumbs(fn (Trail $trail) => $trail->push(__('Home'), route('orbit.index')));

Route::screen('main', MainScreen::class)
    ->name('main')
    ->breadcrumbs(fn (Trail $trail) => $trail->parent('orbit.index')->push(__('Dashboard'), route('orbit.main')));

/*
|--------------------------------------------------------------------------
| Global Search
|--------------------------------------------------------------------------
*/
Route::post('search/{query}', [SearchController::class, 'search'])
    ->where('query', '.*')
    ->name('search');

/*
|--------------------------------------------------------------------------
| Async / AJAX Handlers
|--------------------------------------------------------------------------
*/
Route::post('async', [AsyncController::class, 'load'])
    ->name('async');

Route::post('listener/{screen}/{layout}', [AsyncController::class, 'listener'])
    ->name('async.listener');

/*
|--------------------------------------------------------------------------
| File & Media Management
|--------------------------------------------------------------------------
*/
Route::post('files', [AttachmentController::class, 'upload'])
    ->name('files.upload');

Route::post('media', [AttachmentController::class, 'media'])
    ->name('files.media');

Route::post('files/sort', [AttachmentController::class, 'sort'])
    ->name('files.sort');

Route::delete('files/{id}', [AttachmentController::class, 'destroy'])
    ->name('files.destroy');

Route::put('files/post/{id}', [AttachmentController::class, 'update'])
    ->name('files.update');

/*
|--------------------------------------------------------------------------
| Field Choices
|--------------------------------------------------------------------------
*/
Route::post('choices', ChoicesController::class)
    ->name('choices');

/*
|--------------------------------------------------------------------------
| Sortable / Drag-and-Drop Ordering
|--------------------------------------------------------------------------
*/
Route::post('sorting', [SortableController::class, 'saveSortOrder'])
    ->name('sorting');

/*
|--------------------------------------------------------------------------
| Notifications (optional)
|--------------------------------------------------------------------------
*/
if (config('orbit.notifications.enabled', true)) {

    Route::post('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.markAllAsRead');

    Route::post('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('notifications.unreadCount');

    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.markAsRead');
}

/*
|--------------------------------------------------------------------------
| Media Library
|--------------------------------------------------------------------------
*/
Route::get('media/library', [MediaController::class, 'index'])->name('media.index');
Route::post('media/library/search', [MediaController::class, 'search'])->name('media.search');
Route::post('media/library/upload', [MediaController::class, 'upload'])->name('media.upload');
Route::delete('media/library/{id}', [MediaController::class, 'destroy'])->name('media.destroy');
Route::get('media/library/{id}/status', [MediaController::class, 'status'])->name('media.status');

/*
|--------------------------------------------------------------------------
| Entity CRUD (auto-registered from the Entity registry)
|--------------------------------------------------------------------------
|
| Each registered Entity contributes only the screens its descriptor opts into
| via Entity::crud() (the scaffolded, per-entity source of truth). Soft-delete
| models additionally expose a trash listing. Routes live under
| orbit.entities.{plural}.*; the uriKey is baked in as a route default so the
| generic screens can resolve their descriptor.
*/
app(EntityRegistry::class)->all()->each(function (Entity $entity) {
    $key = $entity::uriKey();
    // Route names are registered relative to the group's "orbit." prefix
    // (applied by RouteServiceProvider). $full holds the resolved name used
    // when cross-referencing other routes (breadcrumb parents).
    $name = 'entities.'.$key;
    $full = 'orbit.'.$name;
    $base = 'entities/'.$key;

    if ($entity->hasCrud('create')) {
        Route::screen($base.'/create', $entity->screenFor('create', CreateScreen::class))
            ->name($name.'.create')
            ->defaults('entity', $key)
            ->breadcrumbs(fn (Trail $trail) => $trail
                ->parent($full.'.index')
                ->push(__('Create')));
    }

    if ($entity->hasCrud('edit')) {
        Route::screen($base.'/{id}/edit', $entity->screenFor('edit', EditScreen::class))
            ->name($name.'.edit')
            ->defaults('entity', $key)
            ->breadcrumbs(fn (Trail $trail) => $trail
                ->parent($entity->hasCrud('view') ? $full.'.view' : $full.'.index')
                ->push(__('Edit')));
    }

    if ($entity->hasCrud('trash')) {
        Route::screen($base.'/trash', $entity->screenFor('trash', TrashScreen::class))
            ->name($name.'.trash')
            ->defaults('entity', $key)
            ->breadcrumbs(fn (Trail $trail) => $trail
                ->parent($full.'.index')
                ->push(__('Trash')));
    }

    if ($entity->hasCrud('view')) {
        Route::screen($base.'/{id}', $entity->screenFor('view', ViewScreen::class))
            ->name($name.'.view')
            ->defaults('entity', $key)
            ->breadcrumbs(fn (Trail $trail) => $trail
                ->parent($full.'.index')
                ->push((string) request()->route('id')));
    }

    if ($entity->hasCrud('list')) {
        Route::screen($base, $entity->screenFor('list', ListScreen::class))
            ->name($name.'.index')
            ->defaults('entity', $key)
            ->breadcrumbs(fn (Trail $trail) => $trail
                ->parent('orbit.index')
                ->push($entity->label()));
    }
});

/*
|--------------------------------------------------------------------------
| Settings hub & per-group configuration screens
|--------------------------------------------------------------------------
*/
Route::screen('configs', SettingsScreen::class)
    ->name('configs')
    ->breadcrumbs(fn (Trail $trail) => $trail->parent('orbit.index')->push(__('Settings')));

Route::screen('configs/{group}', ConfigGroupScreen::class)
    ->name('configs.group')
    ->breadcrumbs(fn (Trail $trail, $group) => $trail->parent('orbit.configs')->push($group));

/*
|--------------------------------------------------------------------------
| Sitemap
|--------------------------------------------------------------------------
*/
Route::get('sitemap.xml', [SitemapController::class, 'index'])
    ->withoutMiddleware(config('orbit.middleware.private'))
    ->name('sitemap');

/*
|--------------------------------------------------------------------------
| Fallback Route (catch-all)
|--------------------------------------------------------------------------
*/
if (config('orbit.fallback', true)) {
    Route::fallback([IndexController::class, 'fallback']);
}
