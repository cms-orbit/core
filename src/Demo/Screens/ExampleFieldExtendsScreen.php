<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Screens;

use CmsOrbit\Core\Demo\DemoScreen;
use CmsOrbit\Core\Screen\Fields\Group;
use CmsOrbit\Core\Screen\Fields\Label;
use CmsOrbit\Core\Screen\Fields\Markdown;
use CmsOrbit\Core\Screen\Fields\Matrix;
use CmsOrbit\Core\Screen\Fields\NumberRange;
use CmsOrbit\Core\Screen\Fields\ReactField;
use CmsOrbit\Core\Screen\Fields\TimeZone;
use CmsOrbit\Core\Screen\Fields\ViewField;
use CmsOrbit\Core\Support\Facades\Layout;

class ExampleFieldExtendsScreen extends DemoScreen
{
    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return [
            'markdown' => '# '.(__('Extended fields'))."\n\n".__('Built-in and composable field types beyond basic inputs.'),
            'timezone' => 'Asia/Seoul',
        ];
    }

    public function name(): ?string
    {
        return __('Extend Fields');
    }

    public function description(): ?string
    {
        return __('An overview of additional field types that extend the default form controls.');
    }

    public function layout(): iterable
    {
        return [
            Layout::tabs([
                __('Matrix & ranges') => Layout::rows([
                    Matrix::make('attributes')
                        ->title(__('Attribute matrix'))
                        ->columns([__('Key'), __('Value')]),

                    NumberRange::make('budget')
                        ->title(__('Number range'))
                        ->min(0)
                        ->max(10000),
                ]),

                __('Markdown & timezone') => Layout::rows([
                    Markdown::make('markdown')
                        ->title('Markdown'),

                    TimeZone::make('timezone')
                        ->title(__('Time zone')),
                ]),

                __('View & React escape hatches') => Layout::split([
                    Layout::rows([
                        ViewField::make('custom_view')
                            ->title(__('View field'))
                            ->view('orbit::dummy.block'),

                        Group::make([
                            Label::make('react')->title(__('React field')),
                            ReactField::make('color')
                                ->component('ColorPicker')
                                ->props(['palette' => ['#17ce91', '#fc8024', '#64748b']]),
                        ]),
                    ]),
                    Layout::view('orbit::dummy.block'),
                ])->ratio('60/40'),
            ]),
        ];
    }
}
