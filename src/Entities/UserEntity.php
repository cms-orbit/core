<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities;

use CmsOrbit\Core\Activity\ActivityLogger;
use CmsOrbit\Core\Attachment\Models\Attachment;
use CmsOrbit\Core\Auth\Enums\LoginProvider;
use CmsOrbit\Core\Auth\Models\UserAccount;
use CmsOrbit\Core\Auth\UserAccountManager;
use CmsOrbit\Core\Crud\Layouts\ResourceFields;
use CmsOrbit\Core\Entities\Concerns\LogsCrudActivity;
use CmsOrbit\Core\Entities\Concerns\RendersAccessBadges;
use CmsOrbit\Core\Entities\Concerns\RendersProviderBadges;
use CmsOrbit\Core\Entities\Screens\UserCreateScreen;
use CmsOrbit\Core\Entities\Screens\UserEditScreen;
use CmsOrbit\Core\Entities\Screens\UserViewScreen;
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
use CmsOrbit\Core\Screen\Fields\ReactField;
use CmsOrbit\Core\Screen\Fields\Select;
use CmsOrbit\Core\Screen\Sight;
use CmsOrbit\Core\Screen\TD;
use CmsOrbit\Core\Support\Facades\Layout;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

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
    use RendersProviderBadges;

    public function model(): string
    {
        return Orbit::model(User::class);
    }

    public function icon(): string
    {
        return 'bs.people';
    }

    public function label(): string
    {
        return __('Users');
    }

    public function singularLabel(): string
    {
        return __('User');
    }

    public function section(): string
    {
        return __('Users & Roles');
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
        return ['roles', 'userAccounts'];
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
        return [
            ...$this->profileFields(),
            ...$this->loginFields(),
            ...$this->roleFields(),
        ];
    }

    /**
     * @return Field[]
     */
    public function profileFields(): array
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
            Input::make('name')->title(__('Name'))->required()->placeholder(__('Jane Doe')),
            $avatar,
        ];
    }

    /**
     * @return Field[]
     */
    public function loginFields(): array
    {
        return [
            ReactField::make('emails')
                ->component('UserEmailsEditor')
                ->title(__('Email addresses'))
                ->help(__('Add multiple email addresses and choose which one is primary for login and notifications.'))
                ->value(fn () => $this->emailsEditorValue())
                ->props([
                    'addLabel'     => __('Add email'),
                    'primaryLabel' => __('Primary'),
                    'addressLabel' => __('Email address'),
                ]),

            Group::make([
                Input::make('login_id')
                    ->title(__('Login ID'))
                    ->placeholder('orbitadmin')
                    ->value(fn () => $this->accountValue('id')),
                Input::make('phone')
                    ->title(__('Phone'))
                    ->type('tel')
                    ->placeholder('01012345678')
                    ->value(fn () => $this->accountValue('phone')),
            ])->widthColumns('1fr 1fr'),

            Password::make('password')
                ->title(__('Password'))
                ->value(fn () => '')
                ->placeholder('••••••••')
                ->help(__('Leave blank to keep the current password.')),

            Group::make([
                CheckBox::make('email_verified')
                    ->title(__('Primary email verified'))
                    ->sendTrueOrFalse()
                    ->value(fn () => $this->accountVerified('email')),
                CheckBox::make('phone_verified')
                    ->title(__('Phone verified'))
                    ->sendTrueOrFalse()
                    ->value(fn () => $this->accountVerified('phone')),
            ])->widthColumns('1fr 1fr'),

            CheckBox::make('must_change_password')
                ->title(__('Require password change on next login'))
                ->help(__('Recommended for newly created administrators.'))
                ->sendTrueOrFalse()
                ->value(true),
        ];
    }

    /**
     * @return Field[]
     */
    public function roleFields(): array
    {
        return [
            Select::make('roles')
                ->title(__('Roles'))
                ->multiple()
                ->fromModel(Orbit::model(Role::class), 'name')
                ->value(fn ($value) => $this->selectedRoleIds($value))
                ->help(__('Roles grant their permissions to the user.')),
        ];
    }

    /**
     * Two-column user form: profile/login on the left, roles/permissions on the right.
     *
     * @return array<int, \CmsOrbit\Core\Screen\Layout>
     */
    public function formLayouts(?Model $user = null): array
    {
        $layouts = [
            Layout::block([
                Layout::split([
                    Layout::blank([
                        (new ResourceFields($this->profileFields()))
                            ->title(__('Profile'))
                            ->unwrapped(),
                        (new ResourceFields($this->loginFields()))
                            ->title(__('Login & security'))
                            ->unwrapped(),
                    ]),
                    Layout::blank([
                        (new ResourceFields($this->roleFields()))
                            ->title(__('Roles'))
                            ->unwrapped(),
                        (new ResourceFields($this->permissionOverrideFields()))
                            ->unwrapped(),
                    ]),
                ])->ratio('66/33'),
            ]),
        ];

        if ($user !== null) {
            $layouts[] = Layout::block([
                Layout::component('UserLinkedAccountsPanel', $this->linkedAccountsPanelProps($user)),
            ])->title(__('Linked accounts'));
        }

        return $layouts;
    }

    /**
     * @return array<int, \CmsOrbit\Core\Screen\Layout>
     */
    public function viewLayouts(Model $user): array
    {
        return [
            Layout::block([
                Layout::component('UserDetailView', $this->viewDetailProps($user)),
            ]),
            Layout::block([
                Layout::component('UserLinkedAccountsPanel', $this->linkedAccountsPanelProps($user)),
            ])->title(__('Linked accounts')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function viewDetailProps(Model $user): array
    {
        if (method_exists($user, 'loadMissing')) {
            $user->loadMissing(['roles', 'userAccounts']);
        }

        $roleIds = collect($user->roles ?? [])->map(fn ($role) => (string) $role->getKey())->values()->all();
        $inheritedPermissions = collect($user->roles ?? [])
            ->pluck('permissions')
            ->filter(fn ($permissions) => is_array($permissions))
            ->reduce(
                fn (Collection $permissions, array $items) => $permissions->merge($this->normalizePermissionMap($items)),
                collect()
            )
            ->all();

        return [
            'name'         => (string) $user->getAttribute('name'),
            'avatar_url'   => $this->resolveAvatarUrl($user),
            'profile_rows' => [
                ['label' => __('ID'), 'value' => (string) $user->getKey()],
                [
                    'label' => __('Status'),
                    'html'  => $this->statusBadge(
                        (bool) $user->getAttribute('email_verified_at'),
                        __('Verified'),
                        __('Unverified'),
                    ),
                ],
                [
                    'label' => __('Registered'),
                    'value' => optional($user->getAttribute('created_at'))->diffForHumans(),
                ],
            ],
            'emails' => method_exists($user, 'emailAccounts')
                ? $user->emailAccounts()->map(fn (UserAccount $account): array => [
                    'address'    => (string) ($account->identifier ?? ''),
                    'is_primary' => (bool) $account->is_primary,
                    'verified'   => $account->verified_at !== null,
                ])->values()->all()
                : [],
            'login_rows' => [
                [
                    'label' => __('Login ID'),
                    'value' => $this->accountValueForUser($user, 'id'),
                ],
                [
                    'label' => __('Phone'),
                    'value' => $this->accountValueForUser($user, 'phone'),
                ],
                [
                    'label' => __('Password change'),
                    'value' => method_exists($user, 'shouldChangePassword') && $user->shouldChangePassword()
                        ? __('Required on next login')
                        : __('Not required'),
                ],
            ],
            'roles'                 => collect($user->roles ?? [])->pluck('name')->map(fn ($name) => (string) $name)->values()->all(),
            'permission_groups'     => $this->permissionGroupsPayload(),
            'explicit_permissions'  => $this->normalizePermissionMap($user->getAttribute('permissions')),
            'inherited_permissions' => $inheritedPermissions,
            'selected_role_ids'     => $roleIds,
        ];
    }

    /**
     * @return array<int, array{group: string, permissions: array<int, array{slug: string, label: string}>}>
     */
    protected function permissionGroupsPayload(): array
    {
        return collect(Orbit::getPermission())
            ->map(fn ($items, $group) => [
                'group'       => (string) __((string) $group),
                'permissions' => collect($items)
                    ->map(fn (array $item) => [
                        'slug'  => (string) $item['slug'],
                        'label' => (string) __((string) ($item['description'] ?? $item['slug'])),
                    ])
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $group) => $group['permissions'] !== [])
            ->values()
            ->all();
    }

    protected function resolveAvatarUrl(Model $user): ?string
    {
        $avatarId = $user->getAttribute('avatar_id');

        if (! is_string($avatarId) || blank($avatarId)) {
            return null;
        }

        $attachmentModelClass = Orbit::model(Attachment::class);
        $attachment = $attachmentModelClass::query()->find($avatarId);

        return $attachment?->url;
    }

    protected function accountValueForUser(Model $user, string $provider): ?string
    {
        if (! method_exists($user, 'userAccounts')) {
            return null;
        }

        return optional($user->userAccounts->firstWhere('provider', $provider))->identifier;
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
                ->set('collapsible', false)
                ->set('defaultCollapsed', false)
                ->set('sectionsDefaultCollapsed', true)
                ->help(__('Role permissions appear as inherited. Click a permission to cycle inherit, allow, or deny.')),
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
            'view'   => UserViewScreen::class,
        ];
    }

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', __('ID'))->sort()->width(80),
            TD::make('name', __('Name'))->sort()->filter(TD::FILTER_TEXT),
            TD::make('email', __('Email'))->sort()->filter(TD::FILTER_TEXT),
            TD::make('linked_providers', __('Linked accounts'))
                ->render(fn (Model $model) => $this->providerBadgesForUser($model, __('None'))),
            TD::make('primary_login', __('Primary login'))
                ->render(fn (Model $model) => method_exists($model, 'primaryLoginAccount')
                    ? (string) optional($model->primaryLoginAccount())->label()
                    : '—'),
            TD::make('created_at', __('Registered'))->sort()->defaultHidden(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function searchColumns(): array
    {
        return ['name', 'email'];
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
            Sight::make('linked_providers', __('Linked accounts'))
                ->render(fn (Model $model) => $this->providerBadgesForUser($model, __('None'))),
            Sight::make('user_accounts', __('Account details'))
                ->render(fn (Model $model) => $this->linkedAccountSummary($model)),
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
            'name'                 => ['required', 'string', 'max:255'],
            'emails'               => ['nullable', 'array'],
            'emails.*.address'     => ['nullable', 'string', 'email', 'max:255'],
            'emails.*.is_primary'  => ['sometimes', 'boolean'],
            'login_id'             => ['nullable', 'string', 'max:255'],
            'phone'                => ['nullable', 'string', 'max:32'],
            'password'             => ['nullable', 'string', 'min:8'],
            'must_change_password' => ['sometimes', 'boolean'],
            'email_verified'       => ['sometimes', 'boolean'],
            'phone_verified'       => ['sometimes', 'boolean'],
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
            app(UserAccountManager::class)->syncManagedAccounts($model, [
                'emails'         => is_array($request->input('emails')) ? $request->input('emails') : [],
                'login_id'       => is_string($request->input('login_id')) ? $request->input('login_id') : null,
                'phone'          => is_string($request->input('phone')) ? $request->input('phone') : null,
                'email_verified' => $request->boolean('email_verified'),
                'phone_verified' => $request->boolean('phone_verified'),
            ]);
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
            ->with(['roles', 'userAccounts'])
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

    /**
     * @return array<string, mixed>
     */
    public function linkedAccountsPanelProps(Model $user): array
    {
        if (! method_exists($user, 'userAccounts')) {
            return [
                'accounts'   => [],
                'title'      => __('Linked accounts'),
                'emptyLabel' => __('No linked accounts yet.'),
            ];
        }

        $user->loadMissing('userAccounts');

        $accounts = $user->userAccounts
            ->map(function (UserAccount $account): array {
                $viewUrl = Route::has('orbit.entities.user-accounts.view')
                    ? route('orbit.entities.user-accounts.view', ['id' => $account->getKey()])
                    : null;

                return [
                    'id'             => $account->getKey(),
                    'provider'       => $account->provider,
                    'provider_label' => LoginProvider::from($account->provider)->label(),
                    'identifier'     => $account->identifier ?: $account->provider_user_id ?: '—',
                    'is_primary'     => (bool) $account->is_primary,
                    'verified'       => $account->verified_at !== null,
                    'verified_at'    => $account->verified_at?->toIso8601String(),
                    'last_used_at'   => $account->last_used_at?->diffForHumans(),
                    'view_url'       => $viewUrl,
                ];
            })
            ->values()
            ->all();

        $manageUrl = Route::has('orbit.entities.user-accounts.index')
            ? route('orbit.entities.user-accounts.index', ['filter' => ['user_id' => $user->getKey()]])
            : null;

        return [
            'accounts'   => $accounts,
            'manage_url' => $manageUrl,
            'title'      => __('Linked accounts'),
            'emptyLabel' => __('No linked accounts yet.'),
        ];
    }

    protected function linkedAccountSummary(Model $model): string
    {
        if (! method_exists($model, 'userAccounts')) {
            return '<span class="text-sm text-gray-400">'.e(__('None')).'</span>';
        }

        /** @var Collection<int, UserAccount> $accounts */
        $accounts = $model->relationLoaded('userAccounts')
            ? $model->getRelation('userAccounts')
            : $model->userAccounts()->get();

        if ($accounts->isEmpty()) {
            return '<span class="text-sm text-gray-400">'.e(__('None')).'</span>';
        }

        return $accounts
            ->map(function (UserAccount $account): string {
                $provider = LoginProvider::from($account->provider);
                $identifier = e($account->identifier ?: $account->provider_user_id ?: '—');
                $primary = $account->is_primary
                    ? ' <span class="text-[10px] font-medium uppercase tracking-wide text-orbit-primary">'.e(__('Primary')).'</span>'
                    : '';

                return $this->providerBadge($provider).' <span class="font-mono text-xs text-gray-700 dark:text-gray-200">'.$identifier.'</span>'.$primary;
            })
            ->implode('<br class="mb-1">');
    }

    /**
     * @return array<int, array{address: string, is_primary: bool}>
     */
    protected function emailsEditorValue(): array
    {
        $user = $this->currentUser();

        if ($user === null) {
            return [['address' => '', 'is_primary' => true]];
        }

        $rows = $user->emailAccounts()
            ->map(fn (UserAccount $account): array => [
                'address'    => (string) ($account->identifier ?? ''),
                'is_primary' => (bool) $account->is_primary,
            ])
            ->filter(fn (array $row): bool => $row['address'] !== '')
            ->values()
            ->all();

        return $rows !== [] ? $rows : [['address' => '', 'is_primary' => true]];
    }

    protected function accountValue(string $provider): ?string
    {
        $user = $this->currentUser();

        if ($user === null || ! method_exists($user, 'userAccounts')) {
            return null;
        }

        return optional($user->userAccounts->firstWhere('provider', $provider))->identifier;
    }

    protected function accountVerified(string $provider): bool
    {
        $user = $this->currentUser();

        if ($user === null || ! method_exists($user, 'userAccounts')) {
            return false;
        }

        return optional($user->userAccounts->firstWhere('provider', $provider))->verified_at !== null;
    }
}
