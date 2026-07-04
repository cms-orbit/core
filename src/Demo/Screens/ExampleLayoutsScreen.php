<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Screens;

use CmsOrbit\Core\Demo\DemoScreen;
use CmsOrbit\Core\Demo\Layouts\TabMenuExample;
use CmsOrbit\Core\Support\Facades\Layout;

class ExampleLayoutsScreen extends DemoScreen
{
    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return [];
    }

    public function name(): ?string
    {
        return __('Layout Overview');
    }

    public function description(): ?string
    {
        return __('A comprehensive guide to the different layout options available.');
    }

    public function layout(): iterable
    {
        $template = Layout::view('orbit::dummy.block');

        return [
            Layout::block($template)
                ->title(__('Block header'))
                ->description(__('Excellent description that editing or views in block')),

            Layout::tabs([
                __('Example Tab 1') => Layout::view('orbit::dummy.block'),
                __('Example Tab 2') => Layout::view('orbit::dummy.block'),
                __('Example Tab 3') => Layout::view('orbit::dummy.block'),
            ]),

            TabMenuExample::class,

            Layout::view('orbit::dummy.block'),

            Layout::columns([
                Layout::view('orbit::dummy.block'),
                Layout::view('orbit::dummy.block'),
                Layout::view('orbit::dummy.block'),
            ]),

            Layout::accordion([
                __('Collapsible Group Item #1') => Layout::view('orbit::dummy.block'),
                __('Collapsible Group Item #2') => Layout::view('orbit::dummy.block'),
                __('Collapsible Group Item #3') => Layout::view('orbit::dummy.block'),
            ]),
        ];
    }
}
