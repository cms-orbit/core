<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Screens;

use CmsOrbit\Core\Demo\DemoScreen;
use CmsOrbit\Core\Support\Facades\Layout;

class ExampleGridScreen extends DemoScreen
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
        return __('Grid System');
    }

    public function description(): ?string
    {
        return __('Use powerful grid to build layouts');
    }

    public function layout(): iterable
    {
        $template = Layout::view('orbit::dummy.block');

        return [
            Layout::split([
                $template,
                $template,
            ])->ratio('30/70')->reverseOnPhone(),

            Layout::split([
                $template,
                $template,
            ])->ratio('40/60'),

            Layout::split([
                $template,
                $template,
            ])->ratio('50/50'),

            Layout::split([
                $template,
                $template,
            ])->ratio('60/40'),

            Layout::split([
                $template,
                $template,
            ])->ratio('70/30'),
        ];
    }
}
