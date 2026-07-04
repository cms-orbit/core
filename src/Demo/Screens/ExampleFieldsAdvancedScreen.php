<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Screens;

use CmsOrbit\Core\Demo\DemoScreen;
use CmsOrbit\Core\Demo\Layouts\ExampleElements;
use CmsOrbit\Core\Foundation\Models\User;
use CmsOrbit\Core\Screen\Fields\Attach;
use CmsOrbit\Core\Screen\Fields\CheckBox;
use CmsOrbit\Core\Screen\Fields\Cropper;
use CmsOrbit\Core\Screen\Fields\DateRange;
use CmsOrbit\Core\Screen\Fields\DateTimer;
use CmsOrbit\Core\Screen\Fields\Group;
use CmsOrbit\Core\Screen\Fields\Input;
use CmsOrbit\Core\Screen\Fields\Map;
use CmsOrbit\Core\Screen\Fields\Matrix;
use CmsOrbit\Core\Screen\Fields\Picture;
use CmsOrbit\Core\Screen\Fields\Radio;
use CmsOrbit\Core\Screen\Fields\Range;
use CmsOrbit\Core\Screen\Fields\Select;
use CmsOrbit\Core\Screen\Fields\Switcher;
use CmsOrbit\Core\Screen\Fields\UTM;
use CmsOrbit\Core\Support\Facades\Layout;

class ExampleFieldsAdvancedScreen extends DemoScreen
{
    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return [
            'name'  => __('Hello! We collected all the fields in one place'),
            'place' => [
                'lat' => 37.181244855427394,
                'lng' => -3.6021993309259415,
            ],
        ];
    }

    public function name(): ?string
    {
        return __('Advanced Form Controls');
    }

    public function description(): ?string
    {
        return __('Examples for creating a wide variety of forms.');
    }

    public function layout(): iterable
    {
        return [
            ExampleElements::class,
            Layout::rows([
                UTM::make('link')
                    ->title(__('UTM link'))
                    ->help(__('Generated UTM link')),

                Matrix::make('matrix')
                    ->columns([
                        __('Attribute'),
                        __('Value'),
                        __('Units'),
                    ]),

                Map::make('place')
                    ->title(__('Object on the map'))
                    ->help(__('Enter the coordinates, or use the search')),
            ]),

            Layout::rows([
                Group::make([
                    Input::make('phone')
                        ->mask('(999) 999-9999')
                        ->title(__('Phone'))
                        ->placeholder(__('Enter phone number'))
                        ->help(__('Number Phone')),

                    Input::make('ip_address')
                        ->title(__('IP address:'))
                        ->placeholder(__('Enter address'))
                        ->help(__('Specifies an address in IPv4 format'))
                        ->mask([
                            'alias' => 'ip',
                        ]),

                    Input::make('license_plate')
                        ->title(__('License plate:'))
                        ->mask([
                            'mask' => '[9-]AAA-999',
                        ]),
                ]),

                Group::make([
                    Input::make('credit_card')
                        ->mask('9999-9999-9999-9999')
                        ->title(__('Credit card:'))
                        ->placeholder(__('Credit card number'))
                        ->help(__('Number is the long set of digits displayed across the front your plastic card')),

                    Input::make('currency')
                        ->title(__('Currency dollar:'))
                        ->mask([
                            'alias' => 'currency',
                        ])->help(__('Some aliases found in the extensions are: email, currency, decimal, integer, date, datetime, etc.')),

                    Input::make('currency_euro')
                        ->title(__('Currency euro:'))
                        ->mask([
                            'mask'         => '€ 999.999.999,99',
                            'numericInput' => true,
                        ]),
                ]),
            ])->title(__('Input mask')),

            Layout::rows([
                Group::make([
                    DateTimer::make('open')
                        ->title(__('Opening date'))
                        ->help(__('The opening event will take place')),

                    DateTimer::make('allowInput')
                        ->title(__('Allow input'))
                        ->required()
                        ->allowInput(),

                    DateTimer::make('enabledTime')
                        ->title(__('Enabled time'))
                        ->enableTime(),
                ]),

                Group::make([
                    DateTimer::make('AllowEmpty')
                        ->title(__('Allow Empty'))
                        ->allowEmpty(),

                    DateTimer::make('AvailableDates')
                        ->title(__('Available Dates'))
                        ->available([
                            now(),
                            now()->addDays(2),
                            now()->addDays(3),
                        ]),

                    DateTimer::make('AvailableDatesPeriod')
                        ->title(__('Available Dates Period'))
                        ->available([
                            ['from' => now(), 'to' => now()->addWeek()],
                        ]),
                ]),

                Group::make([
                    DateTimer::make('format24hr')
                        ->title(__('Format 24hr'))
                        ->enableTime()
                        ->format24hr(),

                    DateTimer::make('custom')
                        ->title(__('Custom format'))
                        ->noCalendar()
                        ->format('h:i K'),

                    DateRange::make('rangeDate')
                        ->title(__('Range date')),
                ]),
            ])->title(__('DateTime')),

            Layout::columns([
                Layout::rows([
                    Select::make('robot.')
                        ->options([
                            'index'   => __('Index'),
                            'noindex' => __('No index'),
                        ])
                        ->multiple()
                        ->title(__('Multiple select'))
                        ->help(__('Allow search bots to index')),

                    Select::make('user')
                        ->fromModel(User::class, 'name')
                        ->title(__('Select for Eloquent model')),
                ])->title(__('Select')),
                Layout::rows([
                    Group::make([
                        CheckBox::make('free-checkbox')
                            ->sendTrueOrFalse()
                            ->title(__('Free checkbox'))
                            ->placeholder(__('Event for free'))
                            ->help(__('Event for free')),

                        Switcher::make('free-switch')
                            ->sendTrueOrFalse()
                            ->title(__('Free switch'))
                            ->placeholder(__('Event for free'))
                            ->help(__('Event for free')),
                    ]),

                    Radio::make('status')
                        ->placeholder(__('Enabled'))
                        ->value(1)
                        ->title(__('Status')),

                    Radio::make('status')
                        ->placeholder(__('Disabled'))
                        ->value(0),

                    Radio::make('status')
                        ->placeholder(__('Pause'))
                        ->value(3),

                    Radio::make('status')
                        ->placeholder(__('Work'))
                        ->value(4),

                ])->title(__('Status')),
            ]),

            Layout::rows([
                Group::make([
                    Range::make('range')
                        ->title(__('Example range'))
                        ->max(5)
                        ->min(0)
                        ->step(1)
                        ->help(__('The track and thumb are both styled to appear the same across browsers.')),

                    Range::make('range_disabled')
                        ->title(__('Disabled range'))
                        ->disabled(),
                ]),
            ])->title(__('Range')),

            Layout::rows([
                Input::make('raw_file')
                    ->type('file')
                    ->title(__('File input example'))
                    ->horizontal(),

                Input::make('raw_files')
                    ->type('file')
                    ->title(__('Multiple files input example'))
                    ->multiple()
                    ->horizontal(),

                Picture::make('picture')
                    ->title(__('Picture'))
                    ->horizontal(),

                Cropper::make('cropper')
                    ->title(__('Cropper'))
                    ->width(500)
                    ->height(300)
                    ->horizontal(),

                Attach::make('image')
                    ->title(__('Upload Image'))
                    ->accept('image/*')
                    ->help(__('Select an image file. You can upload files in any image format, such as JPG, PNG, or GIF.'))
                    ->horizontal(),

                Attach::make('files')
                    ->multiple()
                    ->title(__('Upload files'))
                    ->horizontal(),

            ])->title(__('File upload')),
        ];
    }
}
