<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Screens;

use CmsOrbit\Core\Demo\DemoScreen;
use CmsOrbit\Core\Demo\Layouts\ExampleElements;
use CmsOrbit\Core\Screen\Actions\Button;
use CmsOrbit\Core\Screen\Fields\CheckBox;
use CmsOrbit\Core\Screen\Fields\Group;
use CmsOrbit\Core\Screen\Fields\Input;
use CmsOrbit\Core\Screen\Fields\Label;
use CmsOrbit\Core\Screen\Fields\Password;
use CmsOrbit\Core\Screen\Fields\Radio;
use CmsOrbit\Core\Screen\Fields\Select;
use CmsOrbit\Core\Screen\Fields\TextArea;
use CmsOrbit\Core\Support\Color;
use CmsOrbit\Core\Support\Facades\Alert;
use CmsOrbit\Core\Support\Facades\Layout;

class ExampleFieldsScreen extends DemoScreen
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
        return __('Form Controls');
    }

    public function description(): ?string
    {
        return __('A comprehensive guide to basic form controls, including input fields, buttons, checkboxes, and radio buttons.');
    }

    public function layout(): iterable
    {
        return [
            ExampleElements::class,
            Layout::rows([
                Group::make([
                    Input::make('name')
                        ->title(__('Name'))
                        ->value('John Doe')
                        ->placeholder(__('Enter your name'))
                        ->help(__('Enter your full name.'))
                        ->horizontal(),

                    Input::make('search_query')
                        ->type('search')
                        ->title(__('Search Query'))
                        ->value('How do I shoot web')
                        ->placeholder(__('Search...'))
                        ->help(__('Enter your search query.'))
                        ->horizontal(),
                ]),

                Group::make([
                    Input::make('email')
                        ->type('email')
                        ->title(__('Email'))
                        ->value('bootstrap@example.com')
                        ->placeholder('example@example.com')
                        ->help(__('Enter your email address.'))
                        ->horizontal(),

                    Input::make('website')
                        ->type('url')
                        ->title(__('Website'))
                        ->value('https://orchid.software')
                        ->placeholder('https://example.com')
                        ->help(__('Enter your website URL.'))
                        ->horizontal(),
                ]),

                Group::make([
                    Input::make('phone')
                        ->type('tel')
                        ->title(__('Phone'))
                        ->value('1-(555)-555-5555')
                        ->placeholder(__('Enter phone number'))
                        ->horizontal()
                        ->popover(__('The device autocomplete mechanisms kick in and suggest phone numbers.'))
                        ->help(__('Enter your phone number.')),

                    Input::make('password')
                        ->type('password')
                        ->title(__('Password'))
                        ->value('Password')
                        ->placeholder(__('Enter password'))
                        ->horizontal(),
                ]),

                Group::make([
                    Input::make('quantity')
                        ->type('number')
                        ->title(__('Quantity'))
                        ->value(42)
                        ->placeholder(__('Enter quantity'))
                        ->horizontal(),

                    Input::make('appointment_datetime')
                        ->type('datetime-local')
                        ->title(__('Appointment Date and Time'))
                        ->value('2011-08-19T13:45:00')
                        ->placeholder('YYYY-MM-DDTHH:MM')
                        ->horizontal(),
                ]),

                Group::make([
                    Input::make('event_date')
                        ->type('date')
                        ->title(__('Event Date'))
                        ->value('2011-08-19')
                        ->placeholder('YYYY-MM-DD')
                        ->horizontal(),

                    Input::make('event_month')
                        ->type('month')
                        ->title(__('Event Month'))
                        ->value('2011-08')
                        ->placeholder('YYYY-MM')
                        ->horizontal(),
                ]),

                Group::make([
                    Input::make('week_number')
                        ->type('week')
                        ->title(__('Week Number'))
                        ->value('2011-W33')
                        ->placeholder('YYYY-W##')
                        ->horizontal(),

                    Input::make('event_time')
                        ->type('time')
                        ->title(__('Event Time'))
                        ->value('13:45:00')
                        ->placeholder('HH:MM:SS')
                        ->horizontal(),
                ]),

                Group::make([
                    Input::make('city')
                        ->title(__('City'))
                        ->help(__('Select a city from the list.'))
                        ->datalist([
                            'San Francisco',
                            'New York',
                            'Seattle',
                            'Los Angeles',
                            'Chicago',
                        ])
                        ->horizontal(),

                    Input::make('color_picker')
                        ->type('color')
                        ->title(__('Color Picker'))
                        ->value('#563d7c')
                        ->horizontal(),
                ]),

                Button::make(__('Submit'))
                    ->method('buttonClickProcessing')
                    ->type(Color::BASIC),
            ]),

            Layout::columns([
                Layout::rows([
                    Input::make('name')
                        ->title(__('Full Name:'))
                        ->placeholder(__('Enter full name'))
                        ->required()
                        ->help(__('Please enter your full name')),

                    Input::make('email')
                        ->title(__('Email address'))
                        ->placeholder(__('Email address'))
                        ->help(__("We'll never share your email with anyone else."))
                        ->popover(__('Tooltip - hint that user opens himself.')),

                    Password::make('password')
                        ->title(__('Password'))
                        ->placeholder(__('Password')),

                    Label::make('static')
                        ->title(__('Static:'))
                        ->value('email@example.com'),

                    Select::make('select')
                        ->title(__('Select'))
                        ->options([1 => 'One', 2 => 'Two']),

                    CheckBox::make('checkbox')
                        ->title(__('Checkbox'))
                        ->placeholder(__('Remember me')),

                    Radio::make('radio')
                        ->placeholder(__('Yes'))
                        ->value(1)
                        ->title(__('Radio')),

                    Radio::make('radio')
                        ->placeholder(__('No'))
                        ->value(0),

                    TextArea::make('textarea')
                        ->title(__('Example textarea'))
                        ->rows(6),

                ])->title(__('Base Controls')),
                Layout::rows([
                    Input::make('disabled_input')
                        ->title(__('Disabled Input'))
                        ->placeholder(__('Disabled Input'))
                        ->help(__('A disabled input element is unusable and un-clickable.'))
                        ->disabled(),

                    Select::make('disabled_select')
                        ->title(__('Disabled select'))
                        ->options([1 => 'One', 2 => 'Two'])
                        ->value(0)
                        ->disabled(),

                    TextArea::make('disabled_textarea')
                        ->title(__('Disabled textarea'))
                        ->placeholder(__('Disabled textarea'))
                        ->rows(6)
                        ->disabled(),

                    Input::make('readonly_input')
                        ->title(__('Readonly Input'))
                        ->placeholder(__('Readonly Input'))
                        ->readonly(),

                    CheckBox::make('readonly_checkbox')
                        ->title(__('Readonly Checkbox'))
                        ->placeholder(__('Remember me'))
                        ->disabled(),

                    Radio::make('radio')
                        ->placeholder(__('Yes'))
                        ->value(1)
                        ->title(__('Radio'))
                        ->disabled(),

                    Radio::make('radio')
                        ->placeholder(__('No'))
                        ->value(0)
                        ->disabled(),

                    TextArea::make('readonly_textarea')
                        ->title(__('Readonly textarea'))
                        ->placeholder(__('Readonly textarea'))
                        ->rows(7)
                        ->disabled(),

                ])->title(__('Input States')),
            ]),
        ];
    }

    public function buttonClickProcessing(): void
    {
        Alert::warning(__('Provide contextual feedback messages for typical user actions with the handful of available and flexible alert messages.'));
    }
}
