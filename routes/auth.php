<?php

declare(strict_types=1);

use CmsOrbit\Core\Foundation\Http\Controllers\LocaleController;
use CmsOrbit\Core\Foundation\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

// Admin interface locale switcher (works for guests on the login screen too).
Route::post('locale', [LocaleController::class, 'switch'])->name('locale.switch');

// Auth web routes
if (config('orbit.auth', true)) {
    // Authentication Routes...
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::middleware('throttle:60,1')
        ->post('login', [LoginController::class, 'login'])
        ->name('login.auth');

    Route::get('lock', [LoginController::class, 'resetCookieLockMe'])->name('login.lock');
}

// Note: Orchid registered both GET + POST `switch-logout` to the same URI. The
// unnamed GET variant is dropped here because Wayfinder cannot generate a valid
// (URI-keyed) action dictionary for two routes sharing an identical URI. The
// React impersonation UI submits via the named POST route below.
Route::post('switch-logout', [LoginController::class, 'switchLogout'])->name('switch.logout');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');
