<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Http\Controllers;

use CmsOrbit\Core\Access\Impersonation;
use CmsOrbit\Core\Activity\ActivityLogger;
use CmsOrbit\Core\Activity\Models\OrbitActivity;
use CmsOrbit\Core\Auth\Enums\LoginProvider;
use CmsOrbit\Core\Auth\LocalLoginService;
use CmsOrbit\Core\Auth\LoginMethodRegistry;
use CmsOrbit\Core\Auth\PhoneLoginService;
use CmsOrbit\Core\Auth\Support\LoginIdentifierNormalizer;
use CmsOrbit\Core\Support\Facades\Toast;
use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LoginController extends Controller
{
    /**
     * @var Guard|SessionGuard
     */
    protected $guard;

    public function __construct(
        Auth $auth,
        protected LoginMethodRegistry $methods,
        protected LocalLoginService $localLogin,
        protected PhoneLoginService $phoneLogin,
    ) {
        $this->guard = $auth->guard(config('orbit.guard'));

        $middleware = 'guest';

        if (InstalledVersions::satisfies(new VersionParser, 'laravel/framework', '>11.17.0')) {
            $middleware = RedirectIfAuthenticated::class;
            RedirectIfAuthenticated::redirectUsing(static fn () => route(config('orbit.index')));
        }

        $this->middleware($middleware, [
            'except' => [
                'logout',
                'switchLogout',
            ],
        ]);
    }

    /**
     * @return JsonResponse|RedirectResponse
     */
    public function login(Request $request, CookieJar $cookieJar)
    {
        $request->validate([
            'provider'       => ['nullable', 'string', Rule::in(LoginProvider::values())],
            'identifier'     => ['nullable', 'string'],
            'email'          => ['nullable', 'string'],
            'password'       => ['nullable', 'string'],
            'challenge_code' => ['nullable', 'string'],
        ]);

        $provider = $this->resolveProvider($request);
        $identifier = $this->resolveIdentifier($request);

        $this->ensureIsNotRateLimited($request, $provider, $identifier);

        if ($provider === LoginProvider::Phone) {
            return $this->loginWithPhoneChallenge($request);
        }

        $request->validate([
            'identifier' => ['required_without:email', 'string'],
            'password'   => ['required', 'string'],
        ]);

        try {
            $user = $this->localLogin->authenticate(
                $provider,
                (string) $identifier,
                (string) $request->input('password'),
            );
        } catch (AuthenticationException $exception) {
            RateLimiter::hit($this->throttleKey($request, $provider, $identifier), $this->lockoutSeconds());
            app(ActivityLogger::class)->log(
                event: 'login_failed',
                category: OrbitActivity::CATEGORY_AUTH,
                description: __('Failed sign in'),
                request: $request,
                authIdentifier: $this->authIdentifier($provider, $identifier),
            );

            throw ValidationException::withMessages($this->identifierErrorMessages($provider, $exception->getMessage()));
        }

        $request->attributes->set('orbit_auth_identifier', $this->authIdentifier($provider, $identifier));
        $this->guard->login($user, $request->boolean('remember'));
        RateLimiter::clear($this->throttleKey($request, $provider, $identifier));

        if ($request->boolean('remember')) {
            $userCookie = $cookieJar->forever($this->nameForLock(), $this->guard->id());
            $cookieJar->queue($userCookie);
        }

        return $this->sendLoginResponse($request);
    }

    /**
     * @return RedirectResponse|JsonResponse
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect()->intended(route(config('orbit.index')));
    }

    public function showLoginForm(Request $request): InertiaResponse
    {
        $user = $request->cookie($this->nameForLock());

        /** @var EloquentUserProvider $provider */
        $provider = $this->guard->getProvider();

        $model = $provider->createModel()->find($user);

        return Inertia::render('orbit/auth/login', [
            'action'     => route('orbit.login.auth'),
            'resetUrl'   => route('orbit.login.lock'),
            'appName'    => config('app.name'),
            'isLockUser' => optional($model)->exists ?? false,
            'methods'    => [
                'local'  => $this->localMethods(),
                'social' => $this->socialMethods(),
            ],
            'pendingChallenge' => session('orbit_auth.phone_challenge'),
            'lockUser'         => $model ? [
                'name'       => (string) $model->getAttribute('name'),
                'identifier' => $this->lockUserIdentifier($model),
            ] : null,
        ]);
    }

    public function resetCookieLockMe(CookieJar $cookieJar): RedirectResponse
    {
        $lockUser = $cookieJar->forget($this->nameForLock());

        return redirect()->route('orbit.login')->withCookie($lockUser);
    }

    public function switchLogout(): RedirectResponse
    {
        Impersonation::logout();

        return redirect()->route(config('orbit.index'));
    }

    /**
     * @return RedirectResponse|JsonResponse
     */
    public function logout(Request $request)
    {
        $this->guard->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect('/');
    }

    private function nameForLock(): string
    {
        return sprintf('%s_%s', $this->guard->getName(), '_orbit_lock');
    }

    /**
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(Request $request, LoginProvider $provider, ?string $identifier): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request, $provider, $identifier), $this->maxAttempts())) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request, $provider, $identifier));

        throw ValidationException::withMessages([
            'identifier' => __('Too many login attempts. Please try again in :seconds seconds.', [
                'seconds' => $seconds,
            ]),
        ]);
    }

    protected function throttleKey(Request $request, LoginProvider $provider, ?string $identifier = null): string
    {
        $normalized = LoginIdentifierNormalizer::normalize($provider, $identifier ?? $this->resolveIdentifier($request)) ?? 'guest';

        return Str::transliterate(Str::lower($provider->value.'|'.$normalized.'|'.$request->ip()));
    }

    protected function maxAttempts(): int
    {
        if (! $this->configTableExists()) {
            return 3;
        }

        return max((int) orbit_config('auth_security.login_max_attempts', 3), 1);
    }

    protected function lockoutSeconds(): int
    {
        if (! $this->configTableExists()) {
            return 600;
        }

        return max((int) orbit_config('auth_security.lockout_minutes', 10), 1) * 60;
    }

    protected function configTableExists(): bool
    {
        return once(fn () => Schema::hasTable('orbit_configs'));
    }

    protected function resolveProvider(Request $request): LoginProvider
    {
        $provider = $request->input('provider');

        if (is_string($provider) && in_array($provider, LoginProvider::values(), true)) {
            return LoginProvider::from($provider);
        }

        return LoginProvider::Email;
    }

    protected function resolveIdentifier(Request $request): ?string
    {
        $identifier = $request->input('identifier', $request->input('email'));

        return is_string($identifier) && filled($identifier) ? $identifier : null;
    }

    /**
     * @return array<string, string>
     */
    protected function identifierErrorMessages(LoginProvider $provider, string $message): array
    {
        $messages = ['identifier' => $message];

        if ($provider === LoginProvider::Email) {
            $messages['email'] = $message;
        }

        return $messages;
    }

    /**
     * @return array<int, array{value: string, label: string, inputType: string, placeholder: string}>
     */
    protected function localMethods(): array
    {
        return $this->methods->enabledLocalProviders()
            ->map(fn (LoginProvider $provider): array => [
                'value'       => $provider->value,
                'label'       => $provider->label(),
                'inputType'   => $provider === LoginProvider::Email ? 'email' : ($provider === LoginProvider::Phone ? 'tel' : 'text'),
                'placeholder' => match ($provider) {
                    LoginProvider::Email => 'jane@example.com',
                    LoginProvider::Phone => '01012345678',
                    default              => 'orbitadmin',
                },
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string, url: string}>
     */
    protected function socialMethods(): array
    {
        return $this->methods->enabledSocialProviders()
            ->map(fn (LoginProvider $provider): array => [
                'value' => $provider->value,
                'label' => $provider->label(),
                'url'   => route('orbit.login.social.redirect', ['provider' => $provider->value]),
            ])
            ->all();
    }

    protected function lockUserIdentifier(object $model): string
    {
        if (method_exists($model, 'primaryLoginAccount')) {
            $account = $model->primaryLoginAccount();

            if ($account !== null) {
                return (string) $account->label();
            }
        }

        return (string) ($model->getAttribute('email') ?: $model->getAttribute('name') ?: $model->getKey());
    }

    protected function authIdentifier(LoginProvider $provider, ?string $identifier): string
    {
        $normalized = LoginIdentifierNormalizer::normalize($provider, $identifier) ?? 'unknown';

        return $provider->value.':'.$normalized;
    }

    /**
     * @return RedirectResponse|JsonResponse
     */
    protected function loginWithPhoneChallenge(Request $request)
    {
        $request->validate([
            'identifier' => ['required_without:email', 'string'],
        ]);

        $identifier = (string) $this->resolveIdentifier($request);
        $challengeCode = $request->input('challenge_code');

        try {
            if (! is_string($challengeCode) || blank($challengeCode)) {
                $pending = $this->phoneLogin->sendChallenge($identifier);

                session(['orbit_auth.phone_challenge' => $pending]);
                Toast::info(__('A verification code has been sent.'));

                return back()->withInput($request->except(['password', 'challenge_code']));
            }

            $user = $this->phoneLogin->authenticate($identifier, $challengeCode);
        } catch (AuthenticationException $exception) {
            RateLimiter::hit($this->throttleKey($request, LoginProvider::Phone, $identifier), $this->lockoutSeconds());
            app(ActivityLogger::class)->log(
                event: 'login_failed',
                category: OrbitActivity::CATEGORY_AUTH,
                description: __('Failed sign in'),
                request: $request,
                authIdentifier: $this->authIdentifier(LoginProvider::Phone, $identifier),
            );

            throw ValidationException::withMessages([
                blank($challengeCode) ? 'identifier' : 'challenge_code' => $exception->getMessage(),
            ]);
        }

        $request->attributes->set('orbit_auth_identifier', $this->authIdentifier(LoginProvider::Phone, $identifier));
        $this->guard->login($user, false);
        session()->forget('orbit_auth.phone_challenge');
        RateLimiter::clear($this->throttleKey($request, LoginProvider::Phone, $identifier));

        return $this->sendLoginResponse($request);
    }
}
