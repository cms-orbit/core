<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Providers;

use CmsOrbit\Core\Auth\LocalLoginService;
use CmsOrbit\Core\Auth\LoginMethodRegistry;
use CmsOrbit\Core\Auth\Phone\NullPhoneVerificationSender;
use CmsOrbit\Core\Auth\Phone\PhoneChallengeBroker;
use CmsOrbit\Core\Auth\Phone\PhoneVerificationSender;
use CmsOrbit\Core\Auth\PhoneLoginService;
use CmsOrbit\Core\Auth\UserAccountManager;
use CmsOrbit\Core\Support\Facades\Config as OrbitConfig;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Apple\AppleExtendSocialite;
use SocialiteProviders\Kakao\KakaoExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerSocialiteProviders();
    }

    public function register(): void
    {
        $this->app->singleton(LoginMethodRegistry::class);
        $this->app->singleton(UserAccountManager::class);
        $this->app->singleton(LocalLoginService::class);
        $this->app->singleton(PhoneLoginService::class);
        $this->app->singleton(PhoneVerificationSender::class, NullPhoneVerificationSender::class);
        $this->app->singleton(PhoneChallengeBroker::class, function ($app): PhoneChallengeBroker {
            return new PhoneChallengeBroker(
                $app->make(CacheRepository::class),
                $app->make(PhoneVerificationSender::class),
                $app->make(LoginMethodRegistry::class),
            );
        });

        $this->registerAuthConfigGroup();
    }

    protected function registerSocialiteProviders(): void
    {
        if (! class_exists(SocialiteWasCalled::class)) {
            return;
        }

        Event::listen(SocialiteWasCalled::class, function ($event): void {
            if (class_exists(KakaoExtendSocialite::class)) {
                (new KakaoExtendSocialite)->handle($event);
            }

            if (class_exists(AppleExtendSocialite::class)) {
                (new AppleExtendSocialite)->handle($event);
            }
        });
    }

    protected function registerAuthConfigGroup(): void
    {
        OrbitConfig::registerGroup('Authentication & Security', 860, [
            'icon'        => 'bs.shield-lock',
            'title'       => '인증 및 보안',
            'description' => '로그인 수단, 소셜 인증, 휴대폰 인증 정책을 설정합니다.',
        ]);

        OrbitConfig::registerSection('Authentication & Security', 'methods', [
            'title'    => '로그인 방식',
            'priority' => 30,
        ]);
        OrbitConfig::registerSection('Authentication & Security', 'email', [
            'title'    => '이메일 로그인',
            'priority' => 25,
        ]);
        OrbitConfig::registerSection('Authentication & Security', 'id', [
            'title'    => '아이디 로그인',
            'priority' => 24,
        ]);
        OrbitConfig::registerSection('Authentication & Security', 'phone', [
            'title'    => '휴대폰 로그인',
            'priority' => 23,
        ]);
        OrbitConfig::registerSection('Authentication & Security', 'social', [
            'title'    => '소셜 로그인',
            'priority' => 22,
        ]);

        OrbitConfig::registerItem('Authentication & Security', 'auth_methods.email.enabled', 'switcher', true, 'methods', [
            'title' => '이메일 로그인 사용',
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_methods.id.enabled', 'switcher', false, 'methods', [
            'title' => '아이디 로그인 사용',
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_methods.phone.enabled', 'switcher', false, 'methods', [
            'title' => '휴대폰 로그인 사용',
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_methods.google.enabled', 'switcher', false, 'methods', [
            'title' => 'Google 로그인 사용',
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_methods.kakao.enabled', 'switcher', false, 'methods', [
            'title' => 'Kakao 로그인 사용',
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_methods.apple.enabled', 'switcher', false, 'methods', [
            'title' => 'Apple 로그인 사용',
        ]);

        OrbitConfig::registerItem('Authentication & Security', 'auth_methods.email.require_verification', 'switcher', false, 'email', [
            'title'       => '이메일 인증 후 로그인 허용',
            'visibleWhen' => [
                'auth_methods.email.enabled' => true,
            ],
        ]);

        OrbitConfig::registerItem('Authentication & Security', 'auth_methods.id.min_length', 'number', 4, 'id', [
            'title'       => '아이디 최소 길이',
            'min'         => 1,
            'visibleWhen' => [
                'auth_methods.id.enabled' => true,
            ],
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_methods.id.max_length', 'number', 24, 'id', [
            'title'       => '아이디 최대 길이',
            'min'         => 1,
            'visibleWhen' => [
                'auth_methods.id.enabled' => true,
            ],
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_methods.id.blocked_values', 'textarea', 'admin,manager,ceo', 'id', [
            'title'       => '금지 아이디',
            'description' => '쉼표 또는 줄바꿈으로 여러 아이디를 입력할 수 있습니다.',
            'visibleWhen' => [
                'auth_methods.id.enabled' => true,
            ],
        ]);

        OrbitConfig::registerItem('Authentication & Security', 'auth_methods.phone.verification_channel', 'select', 'sms', 'phone', [
            'title'   => '휴대폰 인증 채널',
            'options' => [
                'sms'      => 'SMS',
                'alimtalk' => '카카오 알림톡',
            ],
            'visibleWhen' => [
                'auth_methods.phone.enabled' => true,
            ],
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_methods.phone.challenge_ttl_seconds', 'number', 300, 'phone', [
            'title'       => '인증번호 유효 시간 (초)',
            'min'         => 60,
            'visibleWhen' => [
                'auth_methods.phone.enabled' => true,
            ],
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_sendgo.access_key', 'secret', null, 'phone', [
            'title'       => 'SendGo Access Key',
            'encrypted'   => true,
            'visibleWhen' => [
                'auth_methods.phone.enabled' => true,
            ],
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_sendgo.endpoint', 'input', null, 'phone', [
            'title'       => 'SendGo Endpoint',
            'visibleWhen' => [
                'auth_methods.phone.enabled' => true,
            ],
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_sendgo.secret_key', 'secret', null, 'phone', [
            'title'       => 'SendGo Secret Key',
            'encrypted'   => true,
            'visibleWhen' => [
                'auth_methods.phone.enabled' => true,
            ],
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_sendgo.sender_key', 'input', null, 'phone', [
            'title'       => 'SendGo SMS 발신키',
            'visibleWhen' => [
                'auth_methods.phone.enabled' => true,
            ],
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_sendgo.kakao_sender_key', 'input', null, 'phone', [
            'title'       => 'SendGo 카카오 발신키',
            'visibleWhen' => [
                'auth_methods.phone.enabled' => true,
            ],
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_sendgo.api_version', 'select', 'v1', 'phone', [
            'title'   => 'SendGo API 버전',
            'options' => [
                'v1' => 'v1',
                'v2' => 'v2',
            ],
            'visibleWhen' => [
                'auth_methods.phone.enabled' => true,
            ],
        ]);

        foreach (['google' => 'Google', 'kakao' => 'Kakao', 'apple' => 'Apple'] as $provider => $label) {
            OrbitConfig::registerItem('Authentication & Security', "auth_social.{$provider}.client_id", 'input', null, 'social', [
                'title'       => "{$label} Client ID",
                'visibleWhen' => [
                    "auth_methods.{$provider}.enabled" => true,
                ],
            ]);
            OrbitConfig::registerItem('Authentication & Security', "auth_social.{$provider}.client_secret", 'secret', null, 'social', [
                'title'       => "{$label} Client Secret",
                'encrypted'   => true,
                'visibleWhen' => [
                    "auth_methods.{$provider}.enabled" => true,
                ],
            ]);
            OrbitConfig::registerItem('Authentication & Security', "auth_social.{$provider}.redirect", 'input', null, 'social', [
                'title'       => "{$label} Redirect URL",
                'visibleWhen' => [
                    "auth_methods.{$provider}.enabled" => true,
                ],
            ]);
        }

        OrbitConfig::registerItem('Authentication & Security', 'auth_social.apple.team_id', 'input', null, 'social', [
            'title'       => 'Apple Team ID',
            'visibleWhen' => [
                'auth_methods.apple.enabled' => true,
            ],
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_social.apple.key_id', 'input', null, 'social', [
            'title'       => 'Apple Key ID',
            'visibleWhen' => [
                'auth_methods.apple.enabled' => true,
            ],
        ]);
        OrbitConfig::registerItem('Authentication & Security', 'auth_social.apple.private_key', 'secret', null, 'social', [
            'title'       => 'Apple Private Key',
            'encrypted'   => true,
            'visibleWhen' => [
                'auth_methods.apple.enabled' => true,
            ],
        ]);
    }
}
