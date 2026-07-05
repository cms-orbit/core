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
            throw new AuthenticationException(__('활성화되지 않은 로그인 방식입니다.'));
        }

        $account = UserAccount::query()
            ->identifier(LoginProvider::Phone, $identifier)
            ->first();

        if ($account === null) {
            throw new AuthenticationException(__('입력한 정보와 일치하는 사용자를 찾을 수 없습니다.'));
        }

        return $this->broker->send($identifier);
    }

    /**
     * @throws AuthenticationException
     */
    public function authenticate(string $identifier, string $code): Authenticatable
    {
        if (! $this->registry->isEnabled(LoginProvider::Phone)) {
            throw new AuthenticationException(__('활성화되지 않은 로그인 방식입니다.'));
        }

        if (! $this->broker->verify($identifier, $code)) {
            throw new AuthenticationException(__('인증번호가 올바르지 않거나 만료되었습니다.'));
        }

        /** @var UserAccount|null $account */
        $account = UserAccount::query()
            ->identifier(LoginProvider::Phone, $identifier)
            ->first();

        $user = $account?->user;

        if (! $user instanceof Authenticatable) {
            throw new AuthenticationException(__('입력한 정보와 일치하는 사용자를 찾을 수 없습니다.'));
        }

        if ($account->verified_at === null) {
            $account->forceFill(['verified_at' => now()])->save();
        }

        $account->markAsUsed();

        return $user;
    }
}
