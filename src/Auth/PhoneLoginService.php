<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Auth;

use CmsOrbit\Core\Auth\Enums\LoginProvider;
use CmsOrbit\Core\Auth\Models\UserAccount;
use CmsOrbit\Core\Auth\Phone\PhoneChallengeBroker;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;

class PhoneLoginService
{
    public function __construct(
        protected LoginMethodRegistry $registry,
        protected PhoneChallengeBroker $broker,
    ) {}

    /**
     * @return array{identifier: string, expires_at: string}
     */
    public function sendChallenge(string $identifier): array
    {
        if (! $this->registry->isEnabled(LoginProvider::Phone)) {
            throw new AuthenticationException(__('This login method is not enabled.'));
        }

        $account = UserAccount::query()
            ->identifier(LoginProvider::Phone, $identifier)
            ->first();

        if ($account === null) {
            throw new AuthenticationException(__('No user matches the information you entered.'));
        }

        return $this->broker->send($identifier);
    }

    /**
     * @throws AuthenticationException
     */
    public function authenticate(string $identifier, string $code): Authenticatable
    {
        if (! $this->registry->isEnabled(LoginProvider::Phone)) {
            throw new AuthenticationException(__('This login method is not enabled.'));
        }

        if (! $this->broker->verify($identifier, $code)) {
            throw new AuthenticationException(__('The verification code is incorrect or has expired.'));
        }

        /** @var UserAccount|null $account */
        $account = UserAccount::query()
            ->identifier(LoginProvider::Phone, $identifier)
            ->first();

        $user = $account?->user;

        if (! $user instanceof Authenticatable) {
            throw new AuthenticationException(__('No user matches the information you entered.'));
        }

        if ($account->verified_at === null) {
            $account->forceFill(['verified_at' => now()])->save();
        }

        $account->markAsUsed();

        return $user;
    }
}
