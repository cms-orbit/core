<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities;

use CmsOrbit\Core\Entities\Concerns\RendersAccessBadges;
use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Foundation\Models\Role;
use CmsOrbit\Core\Foundation\Models\User;
use CmsOrbit\Core\Screen\Fields\Group;
use CmsOrbit\Core\Screen\Fields\Input;
use CmsOrbit\Core\Screen\Fields\Password;
use CmsOrbit\Core\Screen\Fields\Select;
use CmsOrbit\Core\Screen\Sight;
use CmsOrbit\Core\Screen\TD;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Built-in admin entity for system users. Roles and granular permissions are
 * stored as Orbit-style JSON maps, so the generic CRUD save is replaced by a
 * dedicated {@see onSave()} that hashes the password, syncs roles and folds the
 * selected permission slugs back into the `{slug: true}` shape the access layer
 * expects.
 */
class UserEntity extends Entity
{
    use RendersAccessBadges;

    public function model(): string
    {
        return Orbit::model(User::class);
    }

    public function icon(): string
    {
        return 'bs.people';
    }

    public function section(): string
    {
        return __('Access Control');
    }

    public function sort(): int
    {
        return 1000;
    }

    /**
     * Users are not soft-deletable, so the trash listing is intentionally absent.
     *
     * @return array<int, string>
     */
    public function crud(): array
    {
        return ['list', 'create', 'view', 'edit', 'delete'];
    }

    /**
     * @return array<int, string>
     */
    public function with(): array
    {
        return ['roles'];
    }

    /**
     * @return \CmsOrbit\Core\Screen\Field[]
     */
    public function fields(): array
    {
        return [
            Group::make([
                Input::make('name')->title(__('Name'))->required()->placeholder(__('Jane Doe')),
                Input::make('email')->title(__('Email'))->type('email')->required()->placeholder('jane@example.com'),
            ])->widthColumns('1fr 1fr'),

            Password::make('password')
                ->title(__('Password'))
                ->placeholder('••••••••')
                ->help(__('Leave blank to keep the current password.')),

            Group::make([
                Select::make('roles')
                    ->title(__('Roles'))
                    ->multiple()
                    ->fromModel(Orbit::model(Role::class), 'name')
                    ->help(__('Roles grant their permissions to the user.')),
                Select::make('permissions')
                    ->title(__('Direct permissions'))
                    ->multiple()
                    ->options($this->permissionOptions())
                    ->help(__('Granted on top of the roles above.')),
            ])->widthColumns('1fr 1fr'),
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
            TD::make('created_at', __('Registered'))->sort()->defaultHidden(),
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
            Sight::make('email_verified_at', __('Status'))
                ->render(fn (Model $model) => $this->statusBadge((bool) $model->getAttribute('email_verified_at'), __('Verified'), __('Unverified'))),
            Sight::make('roles', __('Roles'))
                ->render(fn (Model $model) => $this->badgeList(
                    collect($model->roles ?? [])->pluck('name')->all(),
                    __('No roles')
                )),
            Sight::make('permissions', __('Direct permissions'))
                ->render(fn (Model $model) => (string) collect($model->getAttribute('permissions') ?? [])->filter()->count()),
            Sight::make('created_at', __('Registered'))
                ->render(fn (Model $model) => optional($model->getAttribute('created_at'))->diffForHumans() ?? '—'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique($model->getTable(), 'email')->ignore($model->getKey()),
            ],
            'password' => $model->exists
                ? ['nullable', 'string', 'min:8']
                : ['required', 'string', 'min:8'],
            'roles' => ['nullable', 'array'],
            'permissions' => ['nullable', 'array'],
        ];
    }

    public function onSave(Request $request, Model $model): void
    {
        $model->forceFill([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'permissions' => $this->permissionMap($request),
        ]);

        if ($request->filled('password')) {
            $model->forceFill(['password' => Hash::make((string) $request->input('password'))]);
        }

        $model->save();

        $model->replaceRoles($request->input('roles', []));
    }

    /**
     * Flatten the registered permission groups into a `slug => description` list
     * used as the select options.
     *
     * @return array<string, string>
     */
    protected function permissionOptions(): array
    {
        return Orbit::getPermission()
            ->collapse()
            ->mapWithKeys(fn (array $item) => [$item['slug'] => $item['description']])
            ->all();
    }

    /**
     * Convert the submitted permission slugs into the `{slug: true}` map stored
     * on the model.
     *
     * @return array<string, bool>
     */
    protected function permissionMap(Request $request): array
    {
        return collect($request->input('permissions', []))
            ->filter()
            ->mapWithKeys(fn ($slug) => [(string) $slug => true])
            ->all();
    }
}
