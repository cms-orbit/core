<?php

declare(strict_types=1);

use CmsOrbit\Core\Foundation\Http\Controllers\ForcePasswordController;
use CmsOrbit\Core\Foundation\Http\Controllers\LocaleController;
use CmsOrbit\Core\Foundation\Http\Controllers\LoginController;
use CmsOrbit\Core\Foundation\Http\Controllers\SocialLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Admin interface locale switcher (works for guests on the login screen too).
Route::post('locale', [LocaleController::class, 'switch'])->name('locale.switch');

// Auth web routes
if (config('orbit.auth', true)) {
    // Authentication Routes...
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.auth');
    Route::get('login/{provider}/redirect', [SocialLoginController::class, 'redirect'])
        ->whereIn('provider', ['google', 'kakao', 'apple'])
        ->name('login.social.redirect');
    Route::get('login/{provider}/callback', [SocialLoginController::class, 'callback'])
        ->whereIn('provider', ['google', 'kakao', 'apple'])
        ->name('login.social.callback');

    Route::get('lock', [LoginController::class, 'resetCookieLockMe'])->name('login.lock');
}

// Note: Orchid registered both GET + POST `switch-logout` to the same URI. The
// unnamed GET variant is dropped here because Wayfinder cannot generate a valid
// (URI-keyed) action dictionary for two routes sharing an identical URI. The
// React impersonation UI submits via the named POST route below.
Route::post('switch-logout', [LoginController::class, 'switchLogout'])->name('switch.logout');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth:'.config('orbit.guard'))->group(function (): void {
    Route::get('profile', function (Request $request) {
        abort_unless(Route::has('orbit.entities.users.edit'), 404);

        return redirect()->route('orbit.entities.users.edit', [
            'id' => $request->user()?->getAuthIdentifier(),
        ]);
    })->name('profile');

    Route::get('password/change-required', [ForcePasswordController::class, 'edit'])
        ->name('password.force.edit');

    Route::put('password/change-required', [ForcePasswordController::class, 'update'])
        ->name('password.force.update');
});
