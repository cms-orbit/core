<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Auth;

use CmsOrbit\Core\Auth\Enums\LoginProvider;
use CmsOrbit\Core\Auth\Models\UserAccount;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

class LocalLoginService
{
    public function __construct(
        protected LoginMethodRegistry $registry,
    ) {}

    /**
     * @throws AuthenticationException
     */
    public function authenticate(LoginProvider $provider, string $identifier, ?string $password = null): Authenticatable
    {
        if (! $provider->isLocal() || ! $this->registry->isEnabled($provider)) {
            throw new AuthenticationException(__('This login method is not enabled.'));
        }

        /** @var UserAccount|null $account */
        $account = UserAccount::query()
            ->identifier($provider, $identifier)
            ->first();

        if ($account === null || ! $account->relationLoaded('user')) {
            $account?->load('user');
        }

        $user = $account?->user;

        if (! $user instanceof Authenticatable) {
            throw new AuthenticationException(__('No user matches the information you entered.'));
        }

        if ($provider === LoginProvider::Email && $this->registry->requiresEmailVerification() && $account->verified_at === null) {
            throw new AuthenticationException(__('Verify your email address before signing in.'));
        }

        if ($password === null || ! Hash::check($password, (string) $user->getAuthPassword())) {
            throw new AuthenticationException(__('No user matches the information you entered.'));
        }

        $account->markAsUsed();

        return $user;
    }
}
