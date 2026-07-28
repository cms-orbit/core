<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Providers;

use CmsOrbit\Core\Filters\Http\Middleware\NormalizeTableFilterQuery;
use CmsOrbit\Core\Foundation\Http\Middleware\Access;
use CmsOrbit\Core\Foundation\Http\Middleware\SetOrbitLocale;
use CmsOrbit\Core\Foundation\Http\Middleware\ShareOrbitInertia;
use CmsOrbit\Core\Foundation\Routing\OrbitAccess;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        Route::middlewareGroup('orbit', [
            SetOrbitLocale::class,
            NormalizeTableFilterQuery::class,
            Access::class,
            ShareOrbitInertia::class,
        ]);

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * The access mode (subdomain|domain|path) determines the domain and prefix
     * applied to every Orbit route group.
     */
    public function map(): void
    {
        // Resolved per request so satellite packages can mount the panel on an
        // instance endpoint (e.g. {endpoint}/settings) while the central panel
        // keeps the orbit.{host} root.
        $access = app(OrbitAccess::class);
        $domain = $access->domain();
        $prefix = $access->prefix();
        $privateMiddleware = $access->middleware();

        // Authenticated dashboard routes.
        Route::domain($domain)
            ->prefix($prefix)
            ->as('orbit.')
            ->middleware($privateMiddleware)
            ->group(Orbit::path('routes/orbit.php'));

        // Public authentication routes. The locale middleware is appended so the
        // login screen is localised even though auth routes skip the "orbit"
        // middleware group.
        Route::domain($domain)
            ->prefix($prefix)
            ->as('orbit.')
            ->middleware([...$access->publicMiddleware(), SetOrbitLocale::class, ShareOrbitInertia::class])
            ->group(Orbit::path('routes/auth.php'));

        // Optional host-application routes file.
        if (file_exists(base_path('routes/orbit.php'))) {
            Route::domain($domain)
                ->prefix($prefix)
                ->middleware($privateMiddleware)
                ->group(base_path('routes/orbit.php'));
        }
    }

    /**
     * Resolve the route domain based on the configured access mode.
     */
    protected function resolveDomain(): ?string
    {
        return match (config('orbit.access.mode', 'subdomain')) {
            'subdomain' => $this->subdomainHost(),
            'domain' => config('orbit.access.domain'),
            default => null,
        };
    }

    /**
     * Build the "orbit.{appHost}" domain used in subdomain mode.
     */
    protected function subdomainHost(): ?string
    {
        $label = (string) config('orbit.access.subdomain', 'orbit');
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return $label.'.'.$host;
    }

    /**
     * Resolve the route prefix; only path mode applies a URL prefix.
     */
    protected function resolvePrefix(): string
    {
        if (config('orbit.access.mode') === 'path') {
            return Str::start((string) config('orbit.access.prefix', 'settings'), '/');
        }

        return '/';
    }
}
