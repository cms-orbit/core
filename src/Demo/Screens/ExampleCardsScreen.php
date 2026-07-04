<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Demo\Screens;

use CmsOrbit\Core\Demo\DemoScreen;
use CmsOrbit\Core\Foundation\Models\User;
use CmsOrbit\Core\Screen\Sight;
use CmsOrbit\Core\Support\Facades\Layout;
use CmsOrbit\Core\Support\Facades\Toast;
use Illuminate\Http\Request;

class ExampleCardsScreen extends DemoScreen
{
    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return [
            'user' => User::query()->firstOrFail(),
        ];
    }

    public function name(): ?string
    {
        return __('Cards');
    }

    public function description(): ?string
    {
        return __('A comprehensive guide to the design and implementation of cards, including basic and advanced features.');
    }

    public function layout(): iterable
    {
        return [
            Layout::legend('user', [
                Sight::make('id')->popover(__('Identifier, a symbol which uniquely identifies an object or record')),
                Sight::make('name'),
                Sight::make('email'),
                Sight::make('email_verified_at', __('Email Verified'))->render(fn (User $user) => $user->email_verified_at === null
                    ? '<i class="text-danger">●</i> '.__('False')
                    : '<i class="text-success">●</i> '.__('True')),
                Sight::make('created_at', __('Created')),
                Sight::make('updated_at', __('Updated')),
                Sight::make(__('Simple Text'))->render(fn () => __('This is a wider card with supporting text below as a natural lead-in to additional content.')),
                Sight::make(__('Action'))->render(fn () => '<button type="button" class="btn btn-default">'.e(__('Show toast')).'</button>'),
            ])->title(__('User')),
        ];
    }

    public function showToast(Request $request): void
    {
        Toast::warning($request->get('toast', __('Hello, world! This is a toast message.')));
    }
}
