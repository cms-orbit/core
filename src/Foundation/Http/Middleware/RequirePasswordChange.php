<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user(config('orbit.guard'));

        if (! $this->requiresPasswordChange($user)) {
            return $next($request);
        }

        if ($request->routeIs(
            'orbit.password.force.edit',
            'orbit.password.force.update',
            'orbit.logout',
            'orbit.switch.logout',
        )) {
            return $next($request);
        }

        return new RedirectResponse(route('orbit.password.force.edit'));
    }

    protected function requiresPasswordChange(?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'shouldChangePassword')) {
            return $user->shouldChangePassword();
        }

        return (bool) $user->getAttribute('must_change_password');
    }
}
