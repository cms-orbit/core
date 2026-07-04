<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Layouts;

use CmsOrbit\Core\Screen\Actions\Menu;
use CmsOrbit\Core\Screen\Layouts\TabMenu;

class TabMenuExample extends TabMenu
{
    /**
     * @return Menu[]
     */
    protected function navigations(): iterable
    {
        return [
            Menu::make(__('Overview layouts'))
                ->route('orbit.demo.layouts'),

            Menu::make(__('Dashboard'))
                ->route('orbit.main'),

            Menu::make(__('Documentation'))
                ->url('https://github.com/cms-orbit/core'),

            Menu::make(__('Sample Screen'))
                ->route('orbit.demo.index')
                ->badge(fn () => 6),
        ];
    }
}
