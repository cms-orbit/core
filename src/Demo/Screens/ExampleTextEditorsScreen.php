<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Screens;

use CmsOrbit\Core\Demo\DemoScreen;
use CmsOrbit\Core\Demo\Layouts\ExampleElements;
use CmsOrbit\Core\Screen\Fields\Code;
use CmsOrbit\Core\Screen\Fields\RichText;
use CmsOrbit\Core\Screen\Fields\SimpleMDE;
use CmsOrbit\Core\Support\Facades\Layout;
use Illuminate\Support\Str;

class ExampleTextEditorsScreen extends DemoScreen
{
    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return [
            'rich_text' => __('Hello! We collected all the fields in one place'),
            'simplemde' => '# Big header',
            'code'      => Str::limit((string) file_get_contents(__FILE__), 500),
        ];
    }

    public function name(): ?string
    {
        return __('Form Text Editors');
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
                SimpleMDE::make('simplemde')
                    ->title('SimpleMDE')
                    ->popover(__('SimpleMDE is a simple, embeddable, and beautiful JS markdown editor')),

                RichText::make('rich_text')
                    ->title('RichText')
                    ->popover(__('Notion-style block editor with slash commands and markdown shortcuts.')),

                Code::make('code')
                    ->title(__('Snippet')),
            ]),
        ];
    }
}
