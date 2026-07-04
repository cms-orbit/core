<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Screens;

use CmsOrbit\Core\Demo\DemoScreen;
use CmsOrbit\Core\Demo\Layouts\ExampleElements;
use CmsOrbit\Core\Screen\Actions\Button;
use CmsOrbit\Core\Screen\Actions\DropDown;
use CmsOrbit\Core\Screen\Actions\Link;
use CmsOrbit\Core\Screen\Fields\Group;
use CmsOrbit\Core\Support\Color;
use CmsOrbit\Core\Support\Facades\Layout;
use CmsOrbit\Core\Support\Facades\Toast;

class ExampleActionsScreen extends DemoScreen
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
        return __('Actions Form Controls');
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
                Group::make([
                    Button::make(__('Primary'))->method('buttonClickProcessing')->type(Color::PRIMARY),
                    Button::make(__('Secondary'))->method('buttonClickProcessing')->type(Color::SECONDARY),
                    Button::make(__('Success'))->method('buttonClickProcessing')->type(Color::SUCCESS),
                    Button::make(__('Danger'))->method('buttonClickProcessing')->type(Color::DANGER),
                    Button::make(__('Warning'))->method('buttonClickProcessing')->type(Color::WARNING),
                    Button::make(__('Info'))->method('buttonClickProcessing')->type(Color::INFO),
                    Button::make(__('Light'))->method('buttonClickProcessing')->type(Color::LIGHT),
                    Button::make(__('Dark'))->method('buttonClickProcessing')->type(Color::DARK),
                    Button::make(__('Default'))->method('buttonClickProcessing')->type(Color::BASIC),
                    Button::make(__('Link'))->method('buttonClickProcessing')->type(Color::LINK),
                ])->autoWidth(),

                Group::make([
                    Link::make(__('Basic Link'))->href('#'),
                    Link::make(__('Open new window'))->href('#')->target('_blank'),
                    Link::make(__('Download File'))->href('#')->download(),
                ])->autoWidth(),
            ]),

            Layout::block(Layout::rows([
                Group::make([
                    DropDown::make(__('Dropdown for Buttons'))
                        ->icon('bs.three-dots-vertical')
                        ->list([
                            Button::make(__('Action'))->method('buttonClickProcessing'),
                            Button::make(__('Another action'))->method('buttonClickProcessing'),
                            Button::make(__('Something else here'))->method('buttonClickProcessing'),
                        ]),

                    DropDown::make(__('Dropdown for Links'))
                        ->icon('bs.three-dots-vertical')
                        ->list([
                            Link::make(__('Action'))->href('#'),
                            Link::make(__('Another action'))->href('#'),
                            Link::make(__('Something else here'))->href('#'),
                        ]),
                ])->autoWidth(),
            ]))
                ->title(__('Dropdowns'))
                ->description(__('Contextual overlays for displaying lists of links and buttons')),

            Layout::block(Layout::rows([
                Group::make([
                    Button::make(__('Submit'))->type(Color::PRIMARY)->disabled(),
                    Button::make(__('Submit'))->disabled(),
                ])->autoWidth(),
            ]))
                ->title(__('Disabled state'))
                ->description(__('A disabled button is unusable and un-clickable.')),

            Layout::block(Layout::rows([
                Button::make(__('Submit'))
                    ->method('buttonClickProcessing')
                    ->confirm(__('Communicating the consequences of the decision.')),
            ]))
                ->title(__('Confirm Dialog'))
                ->description(__('Confirm Dialog is a modal Dialog used to confirm user actions.')),

            Layout::block(Layout::rows([
                Button::make(__('Button'))
                    ->icon('bs.box-arrow-up-right')
                    ->method('buttonClickProcessing'),
            ]))
                ->title(__('Icons Button'))
                ->description(__('This type of button is often used to save space on a user interface and make the action more visually appealing.')),

            Layout::block(Layout::rows([
                Button::make(__('Download'))
                    ->icon('bs.download')
                    ->method('export')
                    ->rawClick(),
            ]))
                ->title(__('Download Button'))
                ->description(__('This button is typically used when a user wants to download a file to their local device.')),

            Layout::block(Layout::rows([
                Button::make('Google')
                    ->action('https://google.com'),
            ]))
                ->title(__('Custom Route'))
                ->description(__('The form is always sent by POST request, but the endpoint can be defined')),
        ];
    }

    public function buttonClickProcessing(): void
    {
        Toast::warning(__('Click Processing'));
    }

    public function export()
    {
        return response()->streamDownload(function () {
            $csv = tap(fopen('php://output', 'wb'), function ($csv) {
                fputcsv($csv, ['header:col1', 'header:col2', 'header:col3']);
            });

            collect([
                ['row1:col1', 'row1:col2', 'row1:col3'],
                ['row2:col1', 'row2:col2', 'row2:col3'],
                ['row3:col1', 'row3:col2', 'row3:col3'],
            ])->each(function (array $row) use ($csv) {
                fputcsv($csv, $row);
            });

            return tap($csv, function ($csv) {
                fclose($csv);
            });
        }, 'File-name.csv');
    }
}
