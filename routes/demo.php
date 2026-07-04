<?php

declare(strict_types=1);

use CmsOrbit\Core\Demo\Screens\ExampleActionsScreen;
use CmsOrbit\Core\Demo\Screens\ExampleCardsScreen;
use CmsOrbit\Core\Demo\Screens\ExampleChartsScreen;
use CmsOrbit\Core\Demo\Screens\ExampleFieldExtendsScreen;
use CmsOrbit\Core\Demo\Screens\ExampleFieldsAdvancedScreen;
use CmsOrbit\Core\Demo\Screens\ExampleFieldsScreen;
use CmsOrbit\Core\Demo\Screens\ExampleGridScreen;
use CmsOrbit\Core\Demo\Screens\ExampleLayoutsScreen;
use CmsOrbit\Core\Demo\Screens\ExampleScreen;
use CmsOrbit\Core\Demo\Screens\ExampleTextEditorsScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

Route::screen('demo/example', ExampleScreen::class)
    ->name('demo.index')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('orbit.index')
        ->push(__('Sample Screen')));

Route::screen('demo/examples/form/fields', ExampleFieldsScreen::class)
    ->name('demo.fields')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('orbit.demo.index')
        ->push(__('Form Elements')));

Route::screen('demo/examples/field-extends', ExampleFieldExtendsScreen::class)
    ->name('demo.field-extends')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('orbit.demo.index')
        ->push(__('Extend Fields')));

Route::screen('demo/examples/form/advanced', ExampleFieldsAdvancedScreen::class)
    ->name('demo.advanced')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('orbit.demo.fields')
        ->push(__('Advanced Form Controls')));

Route::screen('demo/examples/form/editors', ExampleTextEditorsScreen::class)
    ->name('demo.editors')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('orbit.demo.fields')
        ->push(__('Form Text Editors')));

Route::screen('demo/examples/form/actions', ExampleActionsScreen::class)
    ->name('demo.actions')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('orbit.demo.fields')
        ->push(__('Actions Form Controls')));

Route::screen('demo/examples/layouts', ExampleLayoutsScreen::class)
    ->name('demo.layouts')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('orbit.demo.index')
        ->push(__('Overview Layouts')));

Route::screen('demo/examples/grid', ExampleGridScreen::class)
    ->name('demo.grid')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('orbit.demo.index')
        ->push(__('Grid System')));

Route::screen('demo/examples/charts', ExampleChartsScreen::class)
    ->name('demo.charts')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('orbit.demo.index')
        ->push(__('Charts')));

Route::screen('demo/examples/cards', ExampleCardsScreen::class)
    ->name('demo.cards')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('orbit.demo.index')
        ->push(__('Cards')));
