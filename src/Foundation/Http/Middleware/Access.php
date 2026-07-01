<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Http\Middleware;

use Carbon\Carbon;
use Closure;
use CmsOrbit\Core\Access\Impersonation;
use CmsOrbit\Core\Screen\Screen;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

/**
 * Class Access.
 */
class Access
{
    /**
     * @var Guard
     */
    protected $guard;

    /**
     * AccessMiddleware constructor.
     */
    public function __construct(Auth $auth)
    {
        $auth->shouldUse(config('orbit.guard'));
        $this->guard = $auth->guard();
    }

    /**
     * @return ResponseFactory|RedirectResponse|Response|mixed
     */
    public function handle(Request $request, Closure $next, string $permission = 'orbit.index')
    {
        Carbon::setLocale(config('app.locale'));

        if ($this->guard->guest()) {
            return $this->redirectToLogin($request);
        }

        if ($this->guard->user()->hasAccess($permission)) {
            return $next($request);
        }

        if (Impersonation::isSwitch()) {
            return response()->view('orbit::auth.impersonation');
        }

        // The current user is already signed in.
        // It means that he does not have the privileges to view.
        abort(Screen::unaccessed());
    }

    /**
     * Redirect on the application login form.
     *
     *
     * @return Application|ResponseFactory|RedirectResponse|Response
     */
    protected function redirectToLogin(Request $request)
    {
        if ($request->expectsJson()) {
            return response('Unauthorized.', 401);
        }

        if (Route::has('orbit.login')) {
            return redirect()->guest(route('orbit.login'));
        }

        if (Route::has('login')) {
            return redirect()->guest(route('login'));
        }

        abort(401);
    }
}
