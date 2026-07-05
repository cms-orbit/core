<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Auth\Concerns;

use CmsOrbit\Core\Auth\Enums\LoginProvider;
use CmsOrbit\Core\Auth\Models\UserAccount;
use CmsOrbit\Core\Auth\Support\LoginIdentifierNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

trait HasOrbitUserAccounts
{
    protected static function bootHasOrbitUserAccounts(): void
    {
        static::saved(function (Model $user): void {
            if (! method_exists($user, 'ensurePrimaryEmailAccountFromMirror')) {
                return;
            }

            $user->ensurePrimaryEmailAccountFromMirror();
        });
    }

    public function userAccounts(): HasMany
    {
        return $this->hasMany(UserAccount::class, 'user_id')->orderByDesc('is_primary')->orderBy('id');
    }

    public function primaryEmailAccount(): HasOne
    {
        return $this->hasOne(UserAccount::class, 'user_id')
            ->where('provider', LoginProvider::Email->value)
            ->where('is_primary', true);
    }

    public function primaryLoginAccount(): ?UserAccount
    {
        return $this->userAccounts()->first();
    }

    public function accountFor(LoginProvider|string $provider, ?string $identifier): ?UserAccount
    {
        $provider = is_string($provider) ? LoginProvider::from($provider) : $provider;

        return $this->userAccounts()
            ->identifier($provider, $identifier)
            ->first();
    }

    public function ensurePrimaryEmailAccountFromMirror(): void
    {
        $email = $this->getAttribute('email');

        if (! is_string($email) || blank($email)) {
            return;
        }

        $normalizedEmail = LoginIdentifierNormalizer::normalize(LoginProvider::Email, $email);

        if ($normalizedEmail === null) {
            return;
        }

        /** @var UserAccount|null $account */
        $account = $this->userAccounts()
            ->provider(LoginProvider::Email)
            ->where('normalized_identifier', $normalizedEmail)
            ->first();

        if ($account === null) {
            $account = $this->userAccounts()->create([
                'provider'              => LoginProvider::Email->value,
                'identifier'            => $email,
                'normalized_identifier' => $normalizedEmail,
                'is_primary'            => true,
                'verified_at'           => $this->getAttribute('email_verified_at'),
            ]);

            return;
        }

        $this->userAccounts()
            ->provider(LoginProvider::Email)
            ->whereKeyNot($account->getKey())
            ->where('is_primary', true)
            ->update(['is_primary' => false]);

        $account->forceFill([
            'identifier'            => $email,
            'normalized_identifier' => $normalizedEmail,
            'is_primary'            => true,
            'verified_at'           => $this->getAttribute('email_verified_at'),
        ])->saveQuietly();
    }

    public function projectPrimaryEmailAccountToUser(): void
    {
        /** @var UserAccount|null $primary */
        $primary = $this->userAccounts()
            ->provider(LoginProvider::Email)
            ->orderByDesc('is_primary')
            ->orderByDesc('verified_at')
            ->orderBy('id')
            ->first();

        $this->forceFill([
            'email'             => $primary?->identifier,
            'email_verified_at' => $primary?->verified_at,
        ])->saveQuietly();
    }

    /**
     * @return Collection<int, UserAccount>
     */
    public function emailAccounts(): Collection
    {
        return $this->userAccounts()
            ->provider(LoginProvider::Email)
            ->get();
    }
}
