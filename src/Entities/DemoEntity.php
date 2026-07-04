<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities;

use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Foundation\Models\User;
use CmsOrbit\Core\Screen\Field;
use CmsOrbit\Core\Screen\Fields\Code;
use CmsOrbit\Core\Screen\Fields\DateTimer;
use CmsOrbit\Core\Screen\Fields\Group;
use CmsOrbit\Core\Screen\Fields\Input;
use CmsOrbit\Core\Screen\Fields\Quill;
use CmsOrbit\Core\Screen\Fields\Radio;
use CmsOrbit\Core\Screen\Fields\Range;
use CmsOrbit\Core\Screen\Fields\Select;
use CmsOrbit\Core\Screen\Fields\Switcher;
use CmsOrbit\Core\Screen\Fields\TextArea;
use CmsOrbit\Core\Screen\Sight;
use CmsOrbit\Core\Screen\TD;
use CmsOrbit\Core\Support\Facades\Toast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * A read/write showcase Entity registered only when `orbit.demo.enabled` is on
 * (default: outside production). It demonstrates the breadth of the Screen/Field
 * API — grouped fields, most field types and rendered legends — without touching
 * real data: {@see save()} / {@see delete()} are intentionally no-ops so the
 * flow can be explored safely against the existing users table.
 */
class DemoEntity extends Entity
{
    public function model(): string
    {
        return User::class;
    }

    public function label(): string
    {
        return __('Field showcase');
    }

    public function singularLabel(): string
    {
        return __('Field showcase');
    }

    public static function uriKey(): string
    {
        return 'demo-showcase';
    }

    public function icon(): string
    {
        return 'bs.palette';
    }

    public function section(): string
    {
        return (string) config('orbit.demo.section', __('Demo'));
    }

    public function sectionKey(): ?string
    {
        return 'demo';
    }

    public function sort(): int
    {
        return 100;
    }

    /**
     * @return array<int, string>
     */
    public function crud(): array
    {
        return ['list', 'create', 'view', 'edit'];
    }

    /**
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Group::make([
                Input::make('name')->title(__('Text input'))->placeholder(__('Jane Doe'))->required(),
                Input::make('email')->title(__('Email input'))->type('email')->placeholder('jane@example.com'),
            ])->widthColumns('1fr 1fr'),

            Group::make([
                Select::make('plan')
                    ->title(__('Select'))
                    ->options([
                        'free'       => __('Free'),
                        'pro'        => __('Pro'),
                        'enterprise' => __('Enterprise'),
                    ])
                    ->help(__('A single-choice select.')),
                Radio::make('billing')
                    ->title(__('Radio'))
                    ->options([
                        'monthly' => __('Monthly'),
                        'yearly'  => __('Yearly'),
                    ]),
            ])->widthColumns('1fr 1fr'),

            Group::make([
                Switcher::make('newsletter')->title(__('Switcher'))->sendTrueOrFalse(),
                DateTimer::make('starts_at')->title(__('Date picker'))->format('Y-m-d'),
                Range::make('score')->title(__('Range'))->min(0)->max(100),
            ])->widthColumns('1fr 1fr 1fr'),

            TextArea::make('bio')->title(__('Textarea'))->rows(3)->placeholder(__('A short bio…')),

            Quill::make('body')->title(__('Rich text (Quill)')),

            Code::make('snippet')->title(__('Code'))->language('json')->lineNumbers(),
        ];
    }

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', __('ID'))->sort()->width(80),
            TD::make('name', __('Name'))->sort(),
            TD::make('email', __('Email'))->sort(),
        ];
    }

    /**
     * @return Sight[]
     */
    public function legend(): array
    {
        return [
            Sight::make('id', __('ID')),
            Sight::make('name', __('Name')),
            Sight::make('email', __('Email')),
            Sight::make('created_at', __('Created'))
                ->render(fn (Model $model) => optional($model->getAttribute('created_at'))->diffForHumans() ?? '—'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(Model $model): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }

    public function save(Request $request, Model $model): void
    {
        Toast::info(__('Demo mode: your input was validated but not saved.'));
    }

    public function delete(Model $model): void
    {
        Toast::info(__('Demo mode: nothing was deleted.'));
    }
}
