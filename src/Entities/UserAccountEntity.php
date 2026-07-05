<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities;

use CmsOrbit\Core\Auth\Enums\LoginProvider;
use CmsOrbit\Core\Auth\Models\UserAccount;
use CmsOrbit\Core\Entities\Concerns\RendersAccessBadges;
use CmsOrbit\Core\Entities\Concerns\RendersProviderBadges;
use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Foundation\Models\User;
use CmsOrbit\Core\Screen\Sight;
use CmsOrbit\Core\Screen\TD;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class UserAccountEntity extends Entity
{
    use RendersAccessBadges;
    use RendersProviderBadges;

    public function model(): string
    {
        return UserAccount::class;
    }

    public function label(): string
    {
        return __('User Accounts');
    }

    public function singularLabel(): string
    {
        return __('User Account');
    }

    public function icon(): string
    {
        return 'bs.link-45deg';
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
        return 1050;
    }

    public function menuParent(): ?string
    {
        return 'access-control-identity';
    }

    /**
     * @return array<int, string>
     */
    public function crud(): array
    {
        return ['list', 'view', 'delete'];
    }

    /**
     * @return array<int, string>
     */
    public function with(): array
    {
        return ['user'];
    }

    public function fields(): array
    {
        return [];
    }

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', __('ID'))->sort()->width(80),
            TD::make('user_id', __('User'))
                ->sort()
                ->filter(TD::FILTER_SELECT, $this->userOptions())
                ->render(fn (Model $model) => $this->renderUserLink($model)),
            TD::make('provider', __('Provider'))
                ->sort()
                ->filter(TD::FILTER_SELECT, $this->providerOptions())
                ->render(fn (Model $model) => $this->providerBadge(LoginProvider::from((string) $model->getAttribute('provider')))),
            TD::make('identifier', __('Identifier'))
                ->render(fn (Model $model) => e((string) ($model->getAttribute('identifier') ?: $model->getAttribute('provider_user_id') ?: '—'))),
            TD::make('is_primary', __('Primary'))
                ->render(fn (Model $model) => $this->statusBadge(
                    (bool) $model->getAttribute('is_primary'),
                    __('Primary'),
                    __('Secondary'),
                )),
            TD::make('verified_at', __('Verified'))
                ->sort()
                ->render(fn (Model $model) => $this->statusBadge(
                    $model->getAttribute('verified_at') !== null,
                    __('Verified'),
                    __('Unverified'),
                )),
            TD::make('last_used_at', __('Last used'))
                ->sort()
                ->defaultHidden()
                ->render(fn (Model $model) => optional($model->getAttribute('last_used_at'))->diffForHumans() ?? '—'),
        ];
    }

    /**
     * @return Sight[]
     */
    public function legend(): array
    {
        return [
            Sight::make('id', __('ID')),
            Sight::make('user_id', __('User'))
                ->render(fn (Model $model) => $this->renderUserLink($model)),
            Sight::make('provider', __('Provider'))
                ->render(fn (Model $model) => $this->providerBadge(LoginProvider::from((string) $model->getAttribute('provider')))),
            Sight::make('identifier', __('Identifier')),
            Sight::make('provider_user_id', __('Provider user ID')),
            Sight::make('is_primary', __('Primary'))
                ->render(fn (Model $model) => $this->statusBadge(
                    (bool) $model->getAttribute('is_primary'),
                    __('Primary'),
                    __('Secondary'),
                )),
            Sight::make('verified_at', __('Verified at'))
                ->render(fn (Model $model) => optional($model->getAttribute('verified_at'))->diffForHumans() ?? '—'),
            Sight::make('last_used_at', __('Last used'))
                ->render(fn (Model $model) => optional($model->getAttribute('last_used_at'))->diffForHumans() ?? '—'),
            Sight::make('created_at', __('Created'))
                ->render(fn (Model $model) => optional($model->getAttribute('created_at'))->diffForHumans() ?? '—'),
        ];
    }

    public function onDelete(Model $model): void
    {
        if (! $model instanceof UserAccount) {
            $model->delete();

            return;
        }

        $user = $model->user;

        if ($user !== null) {
            $remaining = UserAccount::query()
                ->where('user_id', $user->getKey())
                ->whereKeyNot($model->getKey())
                ->count();

            if ($remaining === 0) {
                throw ValidationException::withMessages([
                    'resource' => __('Cannot delete the last linked account for this user.'),
                ]);
            }
        }

        $model->delete();

        if ($user !== null && method_exists($user, 'projectPrimaryEmailAccountToUser')) {
            $user->projectPrimaryEmailAccountToUser();
        }
    }

    protected function renderUserLink(Model $model): string
    {
        $userModelClass = Orbit::model(User::class);
        $user = $model->getRelationValue('user');

        if (! $user instanceof Model) {
            $userId = $model->getAttribute('user_id');

            if ($userId !== null) {
                $user = $userModelClass::query()->find($userId);
            }
        }

        if (! $user instanceof Model) {
            return '—';
        }

        $label = e((string) ($user->getAttribute('name') ?: $user->getAttribute('email') ?: $user->getKey()));

        if (! Route::has('orbit.entities.users.edit')) {
            return $label;
        }

        $url = e(route('orbit.entities.users.edit', ['id' => $user->getKey()]));

        return '<a href="'.$url.'" class="text-orbit-primary hover:underline">'.$label.'</a>';
    }

    /**
     * @return array<string, string>
     */
    protected function userOptions(): array
    {
        $userModelClass = Orbit::model(User::class);

        return $userModelClass::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(fn (User $user): array => [
                (string) $user->getKey() => (string) ($user->getAttribute('name') ?: $user->getAttribute('email') ?: $user->getKey()),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function providerOptions(): array
    {
        return collect(LoginProvider::cases())
            ->mapWithKeys(fn (LoginProvider $provider): array => [
                $provider->value => $provider->label(),
            ])
            ->all();
    }
}
