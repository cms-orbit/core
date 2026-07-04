<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Analytics;

use CmsOrbit\Core\Analytics\Models\AnalyticsPageview;
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

        $visitorId = (string) ($request->cookie(self::VISITOR_COOKIE) ?: Str::uuid());
        $visitToken = (string) ($request->cookie(self::VISIT_COOKIE) ?: Str::uuid());
        $isEntrance = blank($request->cookie(self::VISIT_COOKIE));
        $user = $request->user((string) config('orbit.guard'));

        AnalyticsPageview::query()->create([
            'instance_id' => $instanceId,
            ...$this->userPayload($user instanceof Model ? $user : null),
            'visitor_hash'   => hash_hmac('sha256', $visitorId, $this->hashKey()),
            'visit_token'    => $visitToken,
            'is_entrance'    => $isEntrance,
            'route_name'     => $request->route()?->getName(),
            'route_uri'      => $this->normalizePath($request->route()?->uri()),
            'page_path'      => $this->normalizePath($request->path()) ?? '/',
            'referrer_host'  => $this->referrerHost($request),
            'browser_family' => $details['browser_family'],
            'device_type'    => $details['device_type'],
            'is_bot'         => $details['is_bot'],
            'visited_on'     => today()->toDateString(),
        ]);

        $this->attachCookie($request, $response, self::VISITOR_COOKIE, $visitorId, self::VISITOR_LIFETIME_MINUTES);
        $this->attachCookie($request, $response, self::VISIT_COOKIE, $visitToken, self::VISIT_LIFETIME_MINUTES);
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
            str_contains($userAgent, 'Edg/')                                             => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera')        => 'Opera',
            str_contains($userAgent, 'SamsungBrowser/')                                  => 'Samsung Internet',
            str_contains($userAgent, 'CriOS') || str_contains($userAgent, 'Chrome/')     => 'Chrome',
            str_contains($userAgent, 'FxiOS') || str_contains($userAgent, 'Firefox/')    => 'Firefox',
            str_contains($userAgent, 'Safari/') && ! str_contains($userAgent, 'Chrome/') => 'Safari',
            str_contains($userAgent, 'Trident/') || str_contains($userAgent, 'MSIE ')    => 'Internet Explorer',
            $isBot                                                                       => 'Bot',
            default                                                                      => 'Unknown',
        };

        $deviceType = match (true) {
            $isBot                                                                                                    => 'bot',
            str_contains($lowered, 'ipad') || str_contains($lowered, 'tablet')                                        => 'tablet',
            str_contains($lowered, 'mobile') || str_contains($lowered, 'iphone') || str_contains($lowered, 'android') => 'mobile',
            default                                                                                                   => 'desktop',
        };

        return [
            'browser_family' => $browser,
            'device_type'    => $deviceType,
            'is_bot'         => $isBot,
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
        return Schema::hasTable('orbit_analytics_pageviews');
    }

    protected function configTableExists(): bool
    {
        return Schema::hasTable('orbit_configs');
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

        $user = $request->user((string) config('orbit.guard'));

        return $user instanceof Model ? $user : null;
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
                'user_id'    => null,
                'user_type'  => null,
                'user_name'  => null,
                'user_email' => null,
            ];
        }

        $name = $user->getAttribute('name');
        $email = $user->getAttribute('email');

        return [
            'user_id'    => (string) $user->getKey(),
            'user_type'  => $user->getMorphClass(),
            'user_name'  => is_string($name) && filled($name) ? $name : null,
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

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return is_string($host) && filled($host) ? $host : null;
    }
}
