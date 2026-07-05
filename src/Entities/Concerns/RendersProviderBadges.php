<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities\Concerns;

use CmsOrbit\Core\Auth\Enums\LoginProvider;
use CmsOrbit\Core\Auth\Models\UserAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Provider-colored badge helpers for user account listings.
 */
trait RendersProviderBadges
{
    protected function providerBadge(LoginProvider $provider): string
    {
        $classes = match ($provider) {
            LoginProvider::Email  => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
            LoginProvider::Id     => 'bg-slate-100 text-slate-700 dark:bg-slate-500/15 dark:text-slate-300',
            LoginProvider::Phone  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
            LoginProvider::Google => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
            LoginProvider::Kakao  => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200',
            LoginProvider::Apple  => 'bg-gray-100 text-gray-700 dark:bg-gray-500/15 dark:text-gray-300',
        };

        return '<span class="mr-1 mb-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium '.$classes.'">'
            .e($provider->label())
            .'</span>';
    }

    /**
     * @param Collection<int, UserAccount>|array<int, UserAccount> $accounts
     */
    protected function providerBadgeList(Collection|array $accounts, string $empty = '—'): string
    {
        $collection = $accounts instanceof Collection ? $accounts : Collection::make($accounts);

        if ($collection->isEmpty()) {
            return '<span class="text-sm text-gray-400">'.e($empty).'</span>';
        }

        return $collection
            ->map(function (UserAccount $account): LoginProvider {
                return LoginProvider::from($account->provider);
            })
            ->unique(fn (LoginProvider $provider): string => $provider->value)
            ->map(fn (LoginProvider $provider): string => $this->providerBadge($provider))
            ->implode('');
    }

    protected function providerBadgesForUser(Model $model, string $empty = '—'): string
    {
        if (! method_exists($model, 'userAccounts')) {
            return '<span class="text-sm text-gray-400">'.e($empty).'</span>';
        }

        /** @var Collection<int, UserAccount> $accounts */
        $accounts = $model->relationLoaded('userAccounts')
            ? $model->getRelation('userAccounts')
            : $model->userAccounts()->get();

        return $this->providerBadgeList($accounts, $empty);
    }
}
