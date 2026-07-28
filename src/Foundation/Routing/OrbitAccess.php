<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Routing;

use Illuminate\Support\Str;

/**
 * Resolves the domain, URL prefix, and middleware for the Orbit admin route
 * groups.
 *
 * The default implementation is driven entirely by `config('orbit.access')`
 * (subdomain / domain / path modes) and is resolved per request, so satellite
 * packages (e.g. cms-orbit/saas) can bind a subclass that varies the mount
 * point by request context — for example serving the panel from an instance
 * subdomain under a "/settings" prefix while the central panel keeps the
 * "orbit.{host}" root.
 */
class OrbitAccess
{
    /**
     * Route/domain constraint for the panel (null = current request host).
     */
    public function domain(): ?string
    {
        return match (config('orbit.access.mode', 'subdomain')) {
            'subdomain' => $this->subdomainHost(),
            'domain' => config('orbit.access.domain'),
            default => null,
        };
    }

    /**
     * URL prefix for the panel. Empty string = domain root.
     */
    public function prefix(): string
    {
        return config('orbit.access.mode') === 'path'
            ? '/'.trim((string) config('orbit.access.prefix', 'settings'), '/')
            : '';
    }

    /**
     * Middleware applied to the authenticated panel route group.
     *
     * @return array<int, string>
     */
    public function middleware(): array
    {
        return (array) config('orbit.middleware.private', []);
    }

    /**
     * Middleware applied to the public auth route group (login, etc.).
     *
     * @return array<int, string>
     */
    public function publicMiddleware(): array
    {
        return (array) config('orbit.middleware.public', []);
    }

    /**
     * Join a path onto the resolved prefix (used for building panel URLs).
     */
    public function url(string $path = ''): string
    {
        return Str::start($this->prefix().$path, '/');
    }

    protected function subdomainHost(): ?string
    {
        $label = (string) config('orbit.access.subdomain', 'orbit');
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return $label.'.'.$host;
    }
}
