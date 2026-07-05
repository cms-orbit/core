<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Providers;

use CmsOrbit\Core\Activity\ActivityLogger;
use CmsOrbit\Core\Activity\Models\OrbitActivity;
use CmsOrbit\Core\Analytics\AnalyticsTracker;
use CmsOrbit\Core\Auth\Support\LoginIdentifierNormalizer;
use CmsOrbit\Core\Support\Facades\Config as OrbitConfig;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActivityLogger::class);
        $this->registerSecurityConfigGroup();
    }

    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            $user = $event->user instanceof Model ? $event->user : null;
            $request = app()->bound('request') ? request() : null;

            app(ActivityLogger::class)->log(
                event: 'login_succeeded',
                category: OrbitActivity::CATEGORY_AUTH,
                description: __('Signed in'),
                subject: $user,
                causer: $user,
                authIdentifier: $this->authIdentifierFromRequest($request) ?? $this->authIdentifierFromUser($user),
            );

            if ($user !== null) {
                app(AnalyticsTracker::class)->attributeCurrentVisitToAuthenticatedUser(
                    $request,
                    $user,
                );
                app(AnalyticsTracker::class)->rotateIdentityAfterLogin($request);
            }
        });

        Event::listen(Failed::class, function (Failed $event): void {
            $user = $event->user instanceof Model ? $event->user : null;

            app(ActivityLogger::class)->log(
                event: 'login_failed',
                category: OrbitActivity::CATEGORY_AUTH,
                description: __('Failed sign in'),
                subject: $user,
                authIdentifier: $this->authIdentifierFromCredentials($event->credentials),
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            $user = $event->user instanceof Model ? $event->user : null;
            $request = app()->bound('request') ? request() : null;

            app(ActivityLogger::class)->log(
                event: 'logged_out',
                category: OrbitActivity::CATEGORY_AUTH,
                description: __('Signed out'),
                subject: $user,
                causer: $user,
                authIdentifier: $this->authIdentifierFromRequest($request) ?? $this->authIdentifierFromUser($user),
            );

            app(AnalyticsTracker::class)->forgetIdentityAfterLogout($request);
        });

        Event::listen(Lockout::class, function (Lockout $event): void {
            app(ActivityLogger::class)->log(
                event: 'locked_out',
                category: OrbitActivity::CATEGORY_SECURITY,
                description: __('Login temporarily locked'),
                request: $event->request,
                authIdentifier: $this->authIdentifierFromRequest($event->request),
                properties: [
                    'provider'   => $event->request->input('provider'),
                    'identifier' => $event->request->input('identifier', $event->request->input('email')),
                ],
            );
        });
    }

    /**
     * @param array<string, mixed> $credentials
     */
    protected function authIdentifierFromCredentials(array $credentials): ?string
    {
        foreach ($credentials as $key => $value) {
            if ($key === 'password') {
                continue;
            }

            if (is_string($value) && filled($value)) {
                return $value;
            }
        }

        return null;
    }

    protected function authIdentifierFromRequest(?Request $request): ?string
    {
        if ($request === null) {
            return null;
        }

        $attribute = $request->attributes->get('orbit_auth_identifier');

        if (is_string($attribute) && filled($attribute)) {
            return $attribute;
        }

        $provider = (string) ($request->input('provider') ?: 'email');
        $value = $request->input('identifier') ?? $request->input('email');

        if (! is_string($value) || ! filled($value)) {
            return null;
        }

        $normalized = LoginIdentifierNormalizer::normalize($provider, $value) ?? $value;

        return $provider.':'.$normalized;
    }

    protected function authIdentifierFromUser(?Model $user): ?string
    {
        if ($user === null) {
            return null;
        }

        foreach (['email', 'name'] as $attribute) {
            $value = $user->getAttribute($attribute);

            if (is_string($value) && filled($value)) {
                return $value;
            }
        }

        return (string) $user->getKey();
    }

    protected function registerSecurityConfigGroup(): void
    {
        OrbitConfig::registerGroup('Authentication & Security', 860, [
            'icon'        => 'bs.shield-lock',
            'title'       => '인증 및 보안',
            'description' => '로그인 잠금과 보안 이벤트 추적 기본값을 설정합니다.',
        ]);

        OrbitConfig::registerSection('Authentication & Security', 'lockout', [
            'title'    => '로그인 잠금',
            'priority' => 20,
        ]);

        OrbitConfig::registerItem('Authentication & Security', 'auth_security.login_max_attempts', 'number', 3, 'lockout', [
            'title'       => '최대 로그인 실패 횟수',
            'description' => '이 횟수만큼 연속 실패하면 로그인을 일시 차단합니다.',
            'min'         => 1,
        ]);

        OrbitConfig::registerItem('Authentication & Security', 'auth_security.lockout_minutes', 'number', 10, 'lockout', [
            'title'       => '잠금 시간 (분)',
            'description' => '로그인 차단이 유지되는 시간을 분 단위로 지정합니다.',
            'min'         => 1,
        ]);
    }
}
