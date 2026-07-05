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
            throw new AuthenticationException(__('활성화되지 않은 로그인 방식입니다.'));
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
            throw new AuthenticationException(__('입력한 정보와 일치하는 사용자를 찾을 수 없습니다.'));
        }

        if ($provider === LoginProvider::Email && $this->registry->requiresEmailVerification() && $account->verified_at === null) {
            throw new AuthenticationException(__('이메일 인증을 완료한 뒤 로그인할 수 있습니다.'));
        }

        if ($password === null || ! Hash::check($password, (string) $user->getAuthPassword())) {
            throw new AuthenticationException(__('입력한 정보와 일치하는 사용자를 찾을 수 없습니다.'));
        }

        $account->markAsUsed();

        return $user;
    }
}
