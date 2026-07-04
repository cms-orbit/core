<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities;

use CmsOrbit\Core\Activity\ActivityLogger;
use CmsOrbit\Core\Entities\Concerns\LogsCrudActivity;
use CmsOrbit\Core\Entities\Concerns\RendersAccessBadges;
use CmsOrbit\Core\Entities\Screens\UserCreateScreen;
use CmsOrbit\Core\Entities\Screens\UserEditScreen;
use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Foundation\Models\Role;
use CmsOrbit\Core\Foundation\Models\User;
use CmsOrbit\Core\Screen\Field;
use CmsOrbit\Core\Screen\Fields\CheckBox;
use CmsOrbit\Core\Screen\Fields\Group;
use CmsOrbit\Core\Screen\Fields\Input;
use CmsOrbit\Core\Screen\Fields\Password;
use CmsOrbit\Core\Screen\Fields\PermissionMatrix;
use CmsOrbit\Core\Screen\Fields\Picture;
use CmsOrbit\Core\Screen\Fields\Select;
use CmsOrbit\Core\Screen\Sight;
use CmsOrbit\Core\Screen\TD;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
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
    use LogsCrudActivity;
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

    public function sectionKey(): string
    {
        return 'access-control';
    }

    public function sort(): int
    {
        return 1000;
    }

    public function menuParent(): ?string
    {
        return 'access-control-identity';
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
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            ...$this->basicFields(),
            ...$this->permissionOverrideFields(),
        ];
    }

    /**
     * @return Field[]
     */
    public function basicFields(): array
    {
        $avatar = Picture::make('avatar_id')
            ->title(__('Profile image'))
            ->help(__('Shown in the admin shell and user menus.'))
            ->targetId()
            ->set('group', 'avatars')
            ->path('avatars');

        if (Route::has('orbit.media.upload')) {
            $avatar->uploadUrl(route('orbit.media.upload'));
        }

        return [
            Group::make([
                Input::make('name')->title(__('Name'))->required()->placeholder(__('Jane Doe')),
                Input::make('email')->title(__('Email'))->type('email')->required()->placeholder('jane@example.com'),
            ])->widthColumns('1fr 1fr'),

            $avatar,

            Password::make('password')
                ->title(__('Password'))
                ->value(fn () => '')
                ->placeholder('••••••••')
                ->help(__('Leave blank to keep the current password.')),

            CheckBox::make('must_change_password')
                ->title(__('Require password change on next login'))
                ->help(__('Recommended for newly created administrators.'))
                ->sendTrueOrFalse()
                ->value(true),

            Select::make('roles')
                ->title(__('Roles'))
                ->multiple()
                ->fromModel(Orbit::model(Role::class), 'name')
                ->value(fn ($value) => $this->selectedRoleIds($value))
                ->help(__('Roles grant their permissions to the user.')),
        ];
    }

    /**
     * @return Field[]
     */
    public function permissionOverrideFields(): array
    {
        return [
            PermissionMatrix::make('permissions')
                ->title(__('Direct permissions'))
                ->permissions(Orbit::getPermission())
                ->inheritedPermissions($this->inheritedRolePermissionMap())
                ->set('rolePermissionsById', $this->rolePermissionMapById())
                ->set('collapsible', true)
                ->set('defaultCollapsed', true)
                ->help(__('Role permissions appear as inherited. Use direct permissions to allow or deny a specific ability.')),
        ];
    }

    /**
     * @return array<string, class-string>
     */
    public function screens(): array
    {
        return [
            'create' => UserCreateScreen::class,
            'edit'   => UserEditScreen::class,
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
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique($model->getTable(), 'email')->ignore($model->getKey()),
            ],
            'password' => $model->exists
                ? ['nullable', 'string', 'min:8']
                : ['required', 'string', 'min:8'],
            'must_change_password' => ['sometimes', 'boolean'],
            'roles'                => ['nullable', 'array'],
            'permissions'          => ['nullable', 'array'],
            'avatar_id'            => ['nullable', 'uuid'],
        ];
    }

    public function onSave(Request $request, Model $model): void
    {
        $shouldLogPasswordChange = $model->exists && $request->filled('password');

        $this->persistWithActivity($request, $model, function () use ($request, $model): void {
            $model->forceFill([
                'name'                 => $request->input('name'),
                'email'                => $request->input('email'),
                'must_change_password' => $request->has('must_change_password')
                    ? $request->boolean('must_change_password')
                    : (! $model->exists || (bool) $model->getAttribute('must_change_password')),
                'permissions' => $this->permissionMap($request, $model),
                'avatar_id'   => $request->filled('avatar_id')
                    ? (string) $request->input('avatar_id')
                    : null,
            ]);

            if ($request->filled('password')) {
                $model->forceFill(['password' => Hash::make((string) $request->input('password'))]);
            }

            $model->save();
            $model->replaceRoles($request->input('roles', []));
        }, ['password', 'remember_token', 'must_change_password']);

        if ($shouldLogPasswordChange) {
            $causer = $request->user(config('orbit.guard'));

            app(ActivityLogger::class)->logPasswordChanged(
                subject: $model,
                causer: $causer instanceof Model ? $causer : null,
            );
        }
    }

    public function onDelete(Model $model): void
    {
        $this->deleteWithActivity($model, fn () => $model->delete());
    }

    /**
     * Convert the submitted permission slugs into the `{slug: true}` map stored
     * on the model.
     *
     * @return array<string, bool>
     */
    protected function permissionMap(Request $request, Model $model): array
    {
        if (! $request->exists('permissions')) {
            return $this->normalizePermissionMap($model->getAttribute('permissions'));
        }

        $permissions = $request->input('permissions', []);

        if (is_array($permissions) && array_is_list($permissions)) {
            return collect($permissions)
                ->filter()
                ->mapWithKeys(fn ($slug) => [(string) $slug => true])
                ->all();
        }

        return $this->normalizePermissionMap($permissions);
    }

    /**
     * @return array<string, bool>
     */
    protected function normalizePermissionMap(mixed $permissions): array
    {
        if (! is_array($permissions)) {
            return [];
        }

        return collect($permissions)
            ->mapWithKeys(function ($enabled, $slug): array {
                $normalized = $this->normalizePermissionValue($enabled);

                return $normalized === null
                    ? []
                    : [(string) $slug => $normalized];
            })
            ->all();
    }

    protected function normalizePermissionValue(mixed $enabled): ?bool
    {
        if (is_bool($enabled)) {
            return $enabled;
        }

        if (in_array($enabled, [1, '1', 'true', 'on', 'yes'], true)) {
            return true;
        }

        if (in_array($enabled, [0, '0', 'false', 'off', 'no'], true)) {
            return false;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    protected function selectedRoleIds(mixed $value): array
    {
        return collect($value instanceof Collection ? $value : [$value])
            ->flatten(1)
            ->map(function ($role): ?string {
                if ($role instanceof Model) {
                    return (string) $role->getKey();
                }

                if (is_array($role) || is_object($role)) {
                    $key = data_get($role, 'id');

                    return $key === null ? null : (string) $key;
                }

                return $role === null || $role === '' ? null : (string) $role;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, bool>
     */
    protected function inheritedRolePermissionMap(): array
    {
        $user = $this->currentUser();

        if ($user === null) {
            return [];
        }

        return collect($user->roles ?? [])
            ->pluck('permissions')
            ->filter(fn ($permissions) => is_array($permissions))
            ->reduce(
                fn (Collection $permissions, array $items) => $permissions->merge($this->normalizePermissionMap($items)),
                collect()
            )
            ->all();
    }

    protected function currentUser(): ?Model
    {
        $id = request()->route('id');

        if (! is_string($id) && ! is_int($id)) {
            return null;
        }

        $userModelClass = Orbit::model(User::class);
        $user = $userModelClass::query()
            ->with('roles')
            ->find($id);

        return $user instanceof Model ? $user : null;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    protected function rolePermissionMapById(): array
    {
        $roleModelClass = Orbit::model(Role::class);

        return $roleModelClass::query()
            ->get(['id', 'permissions'])
            ->mapWithKeys(fn (Role $role) => [
                (string) $role->getKey() => $this->normalizePermissionMap($role->getAttribute('permissions')),
            ])
            ->all();
    }
}
