<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Analytics;

use CmsOrbit\Core\Analytics\Models\AnalyticsPageview;
use CmsOrbit\Core\Analytics\Support\AnalyticsSchemaConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsTracker
{
    public const VISITOR_COOKIE = 'orbit_analytics_visitor';

    public const VISIT_COOKIE = 'orbit_analytics_visit';

    private const VISITOR_LIFETIME_MINUTES = 568800; // 395 days

    private const VISIT_LIFETIME_MINUTES = 30;

    private const FALLBACK_VISITOR_ATTRIBUTION_MINUTES = 10;

    public function track(Request $request, Response $response): void
    {
        if (! $this->analyticsTableExists()) {
            return;
        }

        $instanceId = $this->instanceId();
        $respectDnt = (bool) $this->setting('analytics.respect_do_not_track', true, $instanceId);

        if (! $this->setting('analytics.enabled', true, $instanceId)) {
            return;
        }

        if ($respectDnt && $request->header('DNT') === '1') {
            return;
        }

        if (! $this->isTrackableRequest($request, $response, $instanceId)) {
            return;
        }

        $details = $this->userAgentDetails((string) $request->userAgent());

        if ((bool) $this->setting('analytics.filter_bots', true, $instanceId) && $details['is_bot']) {
            return;
        }

        $this->pruneExpiredIfDue($instanceId, (int) $this->setting('analytics.retention_days', 90, $instanceId));

        $visitorId = $this->cookieValue($request, self::VISITOR_COOKIE) ?? (string) Str::uuid();
        $visitToken = $this->cookieValue($request, self::VISIT_COOKIE) ?? (string) Str::uuid();
        $isEntrance = $this->cookieValue($request, self::VISIT_COOKIE) === null;
        $user = $this->requestUser($request);
        $network = $this->networkPayload($request);

        AnalyticsPageview::query()->create([
            'instance_id' => $instanceId,
            ...$this->userPayload($user),
            'visitor_hash' => hash_hmac('sha256', $visitorId, $this->hashKey()),
            'visit_token' => $visitToken,
            'is_entrance' => $isEntrance,
            'route_name' => $request->route()?->getName(),
            'route_uri' => $this->normalizePath($request->route()?->uri()),
            'page_path' => $this->normalizePath($request->path()) ?? '/',
            'referrer_host' => $this->referrerHost($request),
            'ip_address' => $network['ip_address'],
            'country_code' => $network['country_code'],
            'browser_family' => $details['browser_family'],
            'device_type' => $details['device_type'],
            'user_agent' => $request->userAgent(),
            'is_bot' => $details['is_bot'],
            'visited_on' => today()->toDateString(),
        ]);

        if ($this->cookieValue($request, self::VISITOR_COOKIE) === null) {
            $this->attachCookie($request, $response, self::VISITOR_COOKIE, $visitorId, self::VISITOR_LIFETIME_MINUTES);
        }

        if ($this->cookieValue($request, self::VISIT_COOKIE) === null) {
            $this->attachCookie($request, $response, self::VISIT_COOKIE, $visitToken, self::VISIT_LIFETIME_MINUTES);
        }
    }

    public function attributeCurrentVisitToAuthenticatedUser(?Request $request = null, ?Model $user = null): void
    {
        if (! $this->analyticsTableExists()) {
            return;
        }

        $request ??= $this->currentRequest();
        $user ??= $this->requestUser($request);

        if ($request === null || ! $user instanceof Model) {
            return;
        }

        $instanceId = $this->instanceId();

        if (! $this->setting('analytics.enabled', true, $instanceId)) {
            return;
        }

        $visitToken = $this->cookieValue($request, self::VISIT_COOKIE);
        $visitorId = $this->cookieValue($request, self::VISITOR_COOKIE);

        if ($visitToken === null && $visitorId === null) {
            return;
        }

        $query = AnalyticsPageview::query()
            ->when(
                $instanceId !== null,
                fn ($builder) => $builder->where('instance_id', $instanceId),
                fn ($builder) => $builder->whereNull('instance_id')
            )
            ->whereNull('user_id');

        if ($visitToken !== null) {
            $query
                ->where('visit_token', $visitToken)
                ->where('created_at', '>=', now()->subMinutes(self::VISIT_LIFETIME_MINUTES + 5));
        } else {
            $query
                ->where('visitor_hash', hash_hmac('sha256', $visitorId, $this->hashKey()))
                ->where('created_at', '>=', now()->subMinutes(self::FALLBACK_VISITOR_ATTRIBUTION_MINUTES));
        }

        $query->update($this->userPayload($user));
    }

    public function rotateIdentityAfterLogin(?Request $request = null): void
    {
        $request ??= $this->currentRequest();

        if ($request === null) {
            return;
        }

        cookie()->queue($this->makeCookie(
            $request,
            self::VISITOR_COOKIE,
            (string) Str::uuid(),
            self::VISITOR_LIFETIME_MINUTES,
        ));

        cookie()->queue($this->makeCookie(
            $request,
            self::VISIT_COOKIE,
            (string) Str::uuid(),
            self::VISIT_LIFETIME_MINUTES,
        ));
    }

    public function forgetIdentityAfterLogout(?Request $request = null): void
    {
        $request ??= $this->currentRequest();

        if ($request === null) {
            return;
        }

        cookie()->queue(cookie()->forget(self::VISITOR_COOKIE, '/', $this->cookieDomain()));
        cookie()->queue(cookie()->forget(self::VISIT_COOKIE, '/', $this->cookieDomain()));
    }

    protected function isTrackableRequest(Request $request, Response $response, ?int $instanceId): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->route() === null) {
            return false;
        }

        if ((bool) $this->setting('analytics.exclude_admin_routes', true, $instanceId) && $request->routeIs('orbit.*')) {
            return false;
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return false;
        }

        if ($request->expectsJson() && ! $response->headers->has('X-Inertia')) {
            return false;
        }

        $contentType = strtolower($response->headers->get('Content-Type', ''));

        return $response->headers->has('X-Inertia')
            || str_contains($contentType, 'text/html')
            || str_contains($contentType, 'application/xhtml+xml');
    }

    /**
     * @return array{browser_family: string, device_type: string, is_bot: bool}
     */
    protected function userAgentDetails(string $userAgent): array
    {
        $lowered = Str::lower($userAgent);
        $isBot = preg_match('/bot|crawl|spider|slurp|preview|mediapartners/i', $userAgent) === 1;

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'SamsungBrowser/') => 'Samsung Internet',
            str_contains($userAgent, 'CriOS') || str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'FxiOS') || str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') && ! str_contains($userAgent, 'Chrome/') => 'Safari',
            str_contains($userAgent, 'Trident/') || str_contains($userAgent, 'MSIE ') => 'Internet Explorer',
            $isBot => 'Bot',
            default => 'Unknown',
        };

        $deviceType = match (true) {
            $isBot => 'bot',
            str_contains($lowered, 'ipad') || str_contains($lowered, 'tablet') => 'tablet',
            str_contains($lowered, 'mobile') || str_contains($lowered, 'iphone') || str_contains($lowered, 'android') => 'mobile',
            default => 'desktop',
        };

        return [
            'browser_family' => $browser,
            'device_type' => $deviceType,
            'is_bot' => $isBot,
        ];
    }

    protected function referrerHost(Request $request): ?string
    {
        $referer = (string) $request->headers->get('referer', '');

        if ($referer === '') {
            return null;
        }

        $host = parse_url($referer, PHP_URL_HOST);

        if (! is_string($host) || $host === '' || $host === $request->getHost()) {
            return null;
        }

        return Str::lower($host);
    }

    protected function attachCookie(
        Request $request,
        Response $response,
        string $name,
        string $value,
        int $minutes,
    ): void {
        $response->headers->setCookie($this->makeCookie($request, $name, $value, $minutes));
    }

    protected function pruneExpiredIfDue(?int $instanceId, int $retentionDays): void
    {
        if ($retentionDays <= 0) {
            return;
        }

        $cacheKey = sprintf(
            'orbit.analytics.prune.%s.%s',
            $instanceId ?? 'all',
            today()->toDateString(),
        );

        if (! Cache::add($cacheKey, true, now()->endOfDay())) {
            return;
        }

        AnalyticsPageview::query()
            ->when($instanceId !== null, fn ($query) => $query->where('instance_id', $instanceId))
            ->where('created_at', '<', now()->subDays($retentionDays))
            ->delete();
    }

    protected function normalizePath(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $trimmed = trim($path, '/');

        return $trimmed === '' ? '/' : '/'.$trimmed;
    }

    protected function instanceId(): ?int
    {
        if (! function_exists('instance_context')) {
            return null;
        }

        return instance_context()?->instance->getKey();
    }

    protected function setting(string $key, mixed $default, ?int $instanceId = null): mixed
    {
        if (! $this->configTableExists()) {
            return $default;
        }

        return orbit_config($key, $default, $instanceId);
    }

    protected function hashKey(): string
    {
        return (string) (config('app.key') ?: static::class);
    }

    protected function analyticsTableExists(): bool
    {
        return Schema::connection($this->schemaConnection())->hasTable('orbit_analytics_pageviews');
    }

    protected function configTableExists(): bool
    {
        return Schema::connection($this->schemaConnection())->hasTable('orbit_configs');
    }

    protected function schemaConnection(): string
    {
        return AnalyticsSchemaConnection::for($this->currentRequest());
    }

    protected function currentRequest(): ?Request
    {
        return app()->bound('request') ? request() : null;
    }

    protected function requestUser(?Request $request): ?Model
    {
        if ($request === null) {
            return null;
        }

        $guards = array_values(array_unique(array_filter([
            config('orbit.guard'),
            config('auth.defaults.guard'),
            'web',
        ], fn ($guard) => is_string($guard) && filled($guard))));

        foreach ($guards as $guard) {
            $user = $request->user($guard);

            if ($user instanceof Model) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @return array{ip_address: ?string, country_code: ?string}
     */
    protected function networkPayload(Request $request): array
    {
        return [
            'ip_address' => $this->anonymizeIp($request->ip()),
            'country_code' => app(AnalyticsGeoLocator::class)->countryCode($request),
        ];
    }

    protected function anonymizeIp(?string $ipAddress): ?string
    {
        if ($ipAddress === null || $ipAddress === '') {
            return null;
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ipAddress);
            $parts[3] = '0';

            return implode('.', $parts);
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $segments = explode(':', $ipAddress);
            $segments = array_pad($segments, 8, '0');

            return implode(':', array_slice($segments, 0, 4)).'::';
        }

        return null;
    }

    protected function cookieValue(Request $request, string $name): ?string
    {
        $value = $request->cookie($name);

        return is_string($value) && filled($value) ? $value : null;
    }

    /**
     * @return array{user_id: ?string, user_type: ?string, user_name: ?string, user_email: ?string}
     */
    protected function userPayload(?Model $user): array
    {
        if ($user === null) {
            return [
                'user_id' => null,
                'user_type' => null,
                'user_name' => null,
                'user_email' => null,
            ];
        }

        $name = $user->getAttribute('name');
        $email = $user->getAttribute('email');

        return [
            'user_id' => (string) $user->getKey(),
            'user_type' => $user->getMorphClass(),
            'user_name' => is_string($name) && filled($name) ? $name : null,
            'user_email' => is_string($email) && filled($email) ? Str::lower($email) : null,
        ];
    }

    protected function makeCookie(Request $request, string $name, string $value, int $minutes): Cookie
    {
        return cookie()->make(
            $name,
            $value,
            $minutes,
            '/',
            $this->cookieDomain(),
            $request->isSecure(),
            true,
            false,
            'lax',
        );
    }

    protected function cookieDomain(): ?string
    {
        $domain = config('session.domain');

        if (is_string($domain) && filled($domain)) {
            return ltrim($domain, '.');
        }

        return null;
    }
}
