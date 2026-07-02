<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Http\Middleware;

use Carbon\Carbon;
use Closure;
use CmsOrbit\Core\Support\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Resolves and applies the admin interface locale for every Orbit request.
 *
 * Resolution order:
 *   1. Authenticated user's stored `locale` (if supported)
 *   2. Session value written by the language switcher
 *   3. Configured default locale (Korean out of the box)
 */
class SetOrbitLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $this->resolveLocale($request);

        App::setLocale($locale);
        App::setFallbackLocale(Locale::fallback());
        Carbon::setLocale($locale);

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        $supported = Locale::supported();
        $default = Locale::default();

        $candidates = [
            $request->user()?->getAttribute('locale'),
            $request->session()->get('orbit.locale'),
            $default,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && in_array($candidate, $supported, true)) {
                return $candidate;
            }
        }

        return $default;
    }
}
