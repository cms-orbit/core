<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo;

use CmsOrbit\Core\Foundation\ItemPermission;
use CmsOrbit\Core\Screen\Actions\Menu;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the Demo section menu when {@see config('orbit.demo.enabled')} is on.
 */
class DemoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! (bool) config('orbit.demo.enabled', false)) {
            return;
        }

        $section = (string) config('orbit.demo.section', __('Demo'));

        Orbit::registerSection('demo', 'bs.book', $section, 100, [
            'rail'    => 'bottom',
            'sidebar' => 'bottom',
            'topbar'  => 'right',
        ]);

        Orbit::registerPermission(
            ItemPermission::group($section)
                ->addPermission('orbit.demo', __('Demo screens'))
        );

        $this->app->booted(function () use ($section) {
            $this->registerMenu($section);
        });
    }

    protected function registerMenu(string $section): void
    {
        $indexUrl = Route::has('orbit.demo.index') ? route('orbit.demo.index') : '#';

        Orbit::registerMenuElement(
            Menu::make(__('Demo Screens'))
                ->icon('bs.book')
                ->url($indexUrl)
                ->sort(100)
                ->set('section', $section)
                ->set('sectionKey', 'demo')
                ->set('permission', 'orbit.index')
                ->list([
                    Menu::make(__('Sample Screen'))
                        ->icon('bs.collection')
                        ->route('orbit.demo.index')
                        ->badge(fn () => 6),

                    Menu::make(__('Form Elements'))
                        ->icon('bs.card-list')
                        ->route('orbit.demo.fields')
                        ->active('*/demo/examples/form/*'),

                    Menu::make(__('Extend Fields'))
                        ->icon('bs.input-cursor')
                        ->route('orbit.demo.field-extends')
                        ->active('*/demo/examples/field-extends'),

                    Menu::make(__('Overview Layouts'))
                        ->icon('bs.window-sidebar')
                        ->route('orbit.demo.layouts'),

                    Menu::make(__('Grid System'))
                        ->icon('bs.columns-gap')
                        ->route('orbit.demo.grid'),

                    Menu::make(__('Charts'))
                        ->icon('bs.bar-chart')
                        ->route('orbit.demo.charts'),

                    Menu::make(__('Cards'))
                        ->icon('bs.card-text')
                        ->route('orbit.demo.cards'),
                ])
        );
    }
}
