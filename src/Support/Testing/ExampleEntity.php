<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Support\Testing;

use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Foundation\Models\User;
use CmsOrbit\Core\Screen\Field;
use CmsOrbit\Core\Screen\Fields\Input;
use CmsOrbit\Core\Screen\TD;
use Illuminate\Database\Eloquent\Model;

/**
 * Minimal Entity used to prove the registration contract (auto CRUD/menu/
 * permissions/routes) in tests and demos. Not auto-registered: opt in with
 * Orbit::registerEntities([ExampleEntity::class]).
 */
class ExampleEntity extends Entity
{
    public function model(): string
    {
        return User::class;
    }

    /**
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('name')->title('Name')->required(),
            Input::make('email')->title('Email')->type('email')->required(),
        ];
    }

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', 'ID')->sort(),
            TD::make('name', 'Name'),
            TD::make('email', 'Email'),
        ];
    }

    public function rules(Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ];
    }
}
