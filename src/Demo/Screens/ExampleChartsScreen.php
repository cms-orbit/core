<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Screens;

use CmsOrbit\Core\Demo\DemoScreen;
use CmsOrbit\Core\Demo\Layouts\ChartBarExample;
use CmsOrbit\Core\Demo\Layouts\ChartLineExample;
use CmsOrbit\Core\Demo\Layouts\ChartPercentageExample;
use CmsOrbit\Core\Demo\Layouts\ChartPieExample;
use CmsOrbit\Core\Support\Facades\Layout;

class ExampleChartsScreen extends DemoScreen
{
    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return [
            'charts' => [
                [
                    'name'   => 'Some Data',
                    'values' => [25, 40, 30, 35, 8, 52, 17],
                    'labels' => ['12am-3am', '3am-6am', '6am-9am', '9am-12pm', '12pm-3pm', '3pm-6pm', '6pm-9pm'],
                ],
                [
                    'name'   => 'Another Set',
                    'values' => [25, 50, -10, 15, 18, 32, 27],
                    'labels' => ['12am-3am', '3am-6am', '6am-9am', '9am-12pm', '12pm-3pm', '3pm-6pm', '6pm-9pm'],
                ],
                [
                    'name'   => 'Yet Another',
                    'values' => [15, 20, -3, -15, 58, 12, -17],
                    'labels' => ['12am-3am', '3am-6am', '6am-9am', '9am-12pm', '12pm-3pm', '3pm-6pm', '6pm-9pm'],
                ],
                [
                    'name'   => 'And Last',
                    'values' => [10, 33, -8, -3, 70, 20, -34],
                    'labels' => ['12am-3am', '3am-6am', '6am-9am', '9am-12pm', '12pm-3pm', '3pm-6pm', '6pm-9pm'],
                ],
            ],
        ];
    }

    public function name(): ?string
    {
        return __('Charts');
    }

    public function description(): ?string
    {
        return __('A comprehensive guide to creating and customizing various types of charts, including bar, line, and pie charts.');
    }

    public function layout(): iterable
    {
        return [
            ChartLineExample::make('charts', __('Actions with a Tweet'))
                ->description(__('The total number of interactions a user has with a tweet.')),

            Layout::columns([
                ChartLineExample::make('charts', __('Line Chart'))
                    ->description(__('Visualize data trends with multi-colored line graphs.')),
                ChartBarExample::make('charts', __('Bar Chart'))
                    ->description(__('Compare data sets with colorful bar graphs.')),
            ]),

            Layout::columns([
                ChartPercentageExample::make('charts', __('Percentage Chart'))
                    ->description(__('Display data as visually appealing and modern percentage graphs.')),

                ChartPieExample::make('charts', __('Pie Chart'))
                    ->description(__('Break down data into easy-to-understand pie graphs with modern design.')),
            ]),
        ];
    }
}
