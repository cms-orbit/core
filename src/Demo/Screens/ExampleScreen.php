<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Screens;

use CmsOrbit\Core\Demo\DemoScreen;
use CmsOrbit\Core\Demo\Layouts\ChartBarExample;
use CmsOrbit\Core\Demo\Layouts\ChartLineExample;
use CmsOrbit\Core\Screen\Actions\Button;
use CmsOrbit\Core\Screen\Actions\ModalToggle;
use CmsOrbit\Core\Screen\Components\Cells\Currency;
use CmsOrbit\Core\Screen\Components\Cells\DateTimeSplit;
use CmsOrbit\Core\Screen\Fields\Input;
use CmsOrbit\Core\Screen\Repository;
use CmsOrbit\Core\Screen\TD;
use CmsOrbit\Core\Support\Facades\Layout;
use CmsOrbit\Core\Support\Facades\Toast;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExampleScreen extends DemoScreen
{
    public const TEXT_EXAMPLE = 'Lorem ipsum at sed ad fusce faucibus primis, potenti inceptos ad taciti nisi tristique
    urna etiam, primis ut lacus habitasse malesuada ut. Lectus aptent malesuada mattis ut etiam fusce nec sed viverra,
    semper mattis viverra malesuada quam metus vulputate torquent magna, lobortis nec nostra nibh sollicitudin
    erat in luctus.';

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
            'table' => [
                new Repository(['id' => 100, 'name' => self::TEXT_EXAMPLE, 'price' => 10.24, 'created_at' => '01.01.2020']),
                new Repository(['id' => 200, 'name' => self::TEXT_EXAMPLE, 'price' => 65.9, 'created_at' => '01.01.2020']),
                new Repository(['id' => 300, 'name' => self::TEXT_EXAMPLE, 'price' => 754.2, 'created_at' => '01.01.2020']),
                new Repository(['id' => 400, 'name' => self::TEXT_EXAMPLE, 'price' => 0.1, 'created_at' => '01.01.2020']),
                new Repository(['id' => 500, 'name' => self::TEXT_EXAMPLE, 'price' => 0.15, 'created_at' => '01.01.2020']),
            ],
            'metrics' => [
                'sales'    => ['value' => number_format(6851), 'diff' => 10.08],
                'visitors' => ['value' => number_format(24668), 'diff' => -30.76],
                'orders'   => ['value' => number_format(10000), 'diff' => 0],
                'total'    => number_format(65661),
            ],
        ];
    }

    public function name(): ?string
    {
        return __('Example Screen');
    }

    public function description(): ?string
    {
        return __('Sample Screen Components');
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('Show toast'))
                ->method('showToast')
                ->novalidate()
                ->icon('bs.chat-square-dots'),

            ModalToggle::make(__('Launch demo modal'))
                ->modal('exampleModal')
                ->method('showToast')
                ->icon('bs.window'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::metrics([
                __('Sales Today')    => 'metrics.sales',
                __('Visitors Today') => 'metrics.visitors',
                __('Pending Orders') => 'metrics.orders',
                __('Total Earnings') => 'metrics.total',
            ]),

            Layout::columns([
                ChartLineExample::make('charts', __('Line Chart'))
                    ->description(__('Visualize data trends with multi-colored line graphs.')),

                ChartBarExample::make('charts', __('Bar Chart'))
                    ->description(__('Compare data sets with colorful bar graphs.')),
            ]),

            Layout::table('table', [
                TD::make('id', __('ID'))
                    ->width('100')
                    ->render(fn (Repository $model) => "<img src='https://loremflickr.com/500/300?random={$model->get('id')}'
                              alt='sample'
                              class='mw-100 d-block img-fluid rounded-1 w-100'>
                            <span class='small text-muted mt-1 mb-0'># {$model->get('id')}</span>"),

                TD::make('name', __('Name'))
                    ->width('450')
                    ->render(fn (Repository $model) => Str::limit($model->get('name'), 200)),

                TD::make('price', __('Price'))
                    ->width('100')
                    ->component(Currency::class, before: '$')
                    ->alignRight()
                    ->sort(),

                TD::make('created_at', __('Created'))
                    ->width('100')
                    ->component(DateTimeSplit::class)
                    ->alignRight(),
            ]),

            Layout::modal('exampleModal', Layout::rows([
                Input::make('toast')
                    ->title(__('Messages to display'))
                    ->placeholder(__('Hello world!'))
                    ->help(__('The entered text will be displayed on the right side as a toast.'))
                    ->required(),
            ]))->title(__('Create your own toast message')),
        ];
    }

    public function showToast(Request $request): void
    {
        Toast::warning($request->get('toast', __('Hello, world! This is a toast message.')));
    }
}
