<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Layouts;

use CmsOrbit\Core\Screen\Actions\Menu;
use CmsOrbit\Core\Screen\Layouts\TabMenu;

class ExampleElements extends TabMenu
{
    /**
     * @return Menu[]
     */
    protected function navigations(): iterable
    {
        return [
            Menu::make(__('Basic Elements'))
                ->route('orbit.demo.fields'),

            Menu::make(__('Advanced Elements'))
                ->route('orbit.demo.advanced'),

            Menu::make(__('Text Editors'))
                ->route('orbit.demo.editors'),

            Menu::make(__('Run Actions'))
                ->route('orbit.demo.actions'),
        ];
    }
}
