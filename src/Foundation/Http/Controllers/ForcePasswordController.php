<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Http\Controllers;

use CmsOrbit\Core\Activity\ActivityLogger;
use CmsOrbit\Core\Foundation\Http\Requests\UpdateForcedPasswordRequest;
use CmsOrbit\Core\Support\Facades\Toast;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ForcePasswordController extends Controller
{
    public function edit(Request $request): Response|RedirectResponse
    {
        $user = $request->user(config('orbit.guard'));

        if (! $this->requiresPasswordChange($user)) {
            return redirect()->route(config('orbit.index'));
        }

        return Inertia::render('orbit/auth/force-password', [
            'appName'   => config('app.name'),
            'email'     => (string) $user->getAttribute('email'),
            'action'    => route('orbit.password.force.update'),
            'logoutUrl' => route('orbit.logout'),
        ]);
    }

    public function update(UpdateForcedPasswordRequest $request): RedirectResponse
    {
        $user = $request->user(config('orbit.guard'));

        $user->forceFill([
            'password'             => Hash::make($request->string('password')->toString()),
            'must_change_password' => false,
        ])->save();

        if ($user instanceof Model) {
            app(ActivityLogger::class)->logPasswordChanged(
                subject: $user,
                causer: $user,
                forced: true,
            );
        }

        Toast::info(__('Your password has been updated.'));

        return redirect()->route(config('orbit.index'));
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
