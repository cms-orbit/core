<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Http\Controllers;

use CmsOrbit\Core\Access\Impersonation;
use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    /**
     * @var Guard|SessionGuard
     */
    protected $guard;

    /**
     * Create a new controller instance.
     */
    public function __construct(Auth $auth)
    {
        $this->guard = $auth->guard(config('orbit.guard'));

        /**
         * @deprecated logic for older Laravel versions
         */
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
     * Handle a login request to the application.
     *
     *
     *
     * @return JsonResponse|RedirectResponse
     *
     * @throws ValidationException
     */
    public function login(Request $request, CookieJar $cookieJar)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $auth = $this->guard->attempt(
            $request->only(['email', 'password']),
            $request->boolean('remember')
        );

        if (! $auth) {
            throw ValidationException::withMessages([
                'email' => __('The details you entered did not match our records. Please double-check and try again.'),
            ]);
        }

        if ($request->boolean('remember')) {
            $user = $cookieJar->forever($this->nameForLock(), $this->guard->id());
            $cookieJar->queue($user);
        }

        return $this->sendLoginResponse($request);
    }

    /**
     * Send the response after the user was authenticated.
     *
     *
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
            'action' => route('orbit.login.auth'),
            'resetUrl' => route('orbit.login.lock'),
            'appName' => config('app.name'),
            'isLockUser' => optional($model)->exists ?? false,
            'lockUser' => $model ? [
                'name' => (string) $model->getAttribute('name'),
                'email' => (string) $model->getAttribute('email'),
            ] : null,
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function resetCookieLockMe(CookieJar $cookieJar)
    {
        $lockUser = $cookieJar->forget($this->nameForLock());

        return redirect()->route('orbit.login')->withCookie($lockUser);
    }

    /**
     * @return RedirectResponse
     */
    public function switchLogout()
    {
        Impersonation::logout();

        return redirect()->route(config('orbit.index'));
    }

    /**
     * Log the user out of the application.
     *
     *
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

    /**
     * Get a unique identifier for the auth session value.
     */
    private function nameForLock(): string
    {
        return sprintf('%s_%s', $this->guard->getName(), '_orchid_lock');
    }
}
