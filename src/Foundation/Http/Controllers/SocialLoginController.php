<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Http\Controllers;

use CmsOrbit\Core\Auth\Enums\LoginProvider;
use CmsOrbit\Core\Auth\Models\UserAccount;
use CmsOrbit\Core\Auth\UserAccountManager;
use CmsOrbit\Core\Foundation\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    /**
     * @var Guard|SessionGuard
     */
    protected $guard;

    public function __construct(
        Auth $auth,
        protected UserAccountManager $accounts,
    ) {
        $this->guard = $auth->guard(config('orbit.guard'));
    }

    public function redirect(string $provider): RedirectResponse
    {
        $loginProvider = $this->provider($provider);
        $this->configureService($loginProvider);

        abort_unless(class_exists(Socialite::class), 500, 'Laravel Socialite is not installed.');

        return Socialite::driver($loginProvider->value)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $loginProvider = $this->provider($provider);
        $this->configureService($loginProvider);

        abort_unless(class_exists(Socialite::class), 500, 'Laravel Socialite is not installed.');

        $socialUser = Socialite::driver($loginProvider->value)->user();

        $providerUserId = (string) ($socialUser->getId() ?? $socialUser->id ?? '');
        abort_if($providerUserId === '', 422, 'Provider user id is missing.');

        /** @var UserAccount|null $linkedAccount */
        $linkedAccount = UserAccount::query()
            ->where('provider', $loginProvider->value)
            ->where('provider_user_id', $providerUserId)
            ->first();

        $email = $socialUser->getEmail();

        $user = $linkedAccount?->user;

        if (! $user instanceof Model && is_string($email) && $email !== '') {
            $user = $this->resolveUserModelClass()::query()
                ->where('email', Str::lower($email))
                ->first();
        }

        if (! $user instanceof Model) {
            $user = $this->resolveUserModelClass()::query()->create([
                'name'                 => $socialUser->getName() ?: $socialUser->getNickname() ?: ucfirst($loginProvider->value).' User',
                'email'                => is_string($email) && $email !== '' ? Str::lower($email) : null,
                'email_verified_at'    => is_string($email) && $email !== '' ? now() : null,
                'password'             => null,
                'must_change_password' => false,
                'permissions'          => [],
            ]);
        }

        $this->accounts->upsertSocialAccount(
            user: $user,
            provider: $loginProvider,
            providerUserId: $providerUserId,
            identifier: is_string($email) && $email !== '' ? Str::lower($email) : null,
            accessToken: $socialUser->token ?? null,
            refreshToken: $socialUser->refreshToken ?? null,
            meta: [
                'name'     => $socialUser->getName(),
                'nickname' => $socialUser->getNickname(),
                'avatar'   => $socialUser->getAvatar(),
            ],
        );

        if (is_string($email) && $email !== '') {
            UserAccount::query()->updateOrCreate(
                [
                    'provider'              => LoginProvider::Email->value,
                    'normalized_identifier' => Str::lower($email),
                ],
                [
                    'user_id'     => $user->getKey(),
                    'identifier'  => Str::lower($email),
                    'is_primary'  => blank($user->getAttribute('email')) || Str::lower((string) $user->getAttribute('email')) === Str::lower($email),
                    'verified_at' => now(),
                ],
            );

            if (blank($user->getAttribute('email'))) {
                $user->forceFill([
                    'email'             => Str::lower($email),
                    'email_verified_at' => now(),
                    'password'          => $user->getAttribute('password') ?: Hash::make(Str::random(40)),
                ])->save();
            }
        }

        $request->attributes->set('orbit_auth_identifier', $loginProvider->value.':'.$providerUserId);
        $this->guard->login($user);

        return redirect()->intended(route(config('orbit.index')));
    }

    protected function provider(string $provider): LoginProvider
    {
        abort_unless(in_array($provider, [LoginProvider::Google->value, LoginProvider::Kakao->value, LoginProvider::Apple->value], true), 404);

        return LoginProvider::from($provider);
    }

    protected function configureService(LoginProvider $provider): void
    {
        config()->set("services.{$provider->value}", array_filter([
            'client_id'     => orbit_config("auth_social.{$provider->value}.client_id"),
            'client_secret' => orbit_config("auth_social.{$provider->value}.client_secret"),
            'redirect'      => orbit_config("auth_social.{$provider->value}.redirect"),
            'team_id'       => orbit_config('auth_social.apple.team_id'),
            'key_id'        => orbit_config('auth_social.apple.key_id'),
            'private_key'   => orbit_config('auth_social.apple.private_key'),
        ], static fn ($value): bool => $value !== null && $value !== ''));
    }

    /**
     * @return class-string<Model>
     */
    protected function resolveUserModelClass(): string
    {
        $guard = (string) config('orbit.guard', config('auth.defaults.guard', 'web'));
        $provider = config("auth.guards.{$guard}.provider");
        $modelClass = is_string($provider)
            ? config("auth.providers.{$provider}.model")
            : null;

        return is_string($modelClass) && class_exists($modelClass)
            ? $modelClass
            : User::class;
    }
}
