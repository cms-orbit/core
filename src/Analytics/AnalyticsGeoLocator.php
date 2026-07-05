<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Analytics;

use Illuminate\Http\Request;

class AnalyticsGeoLocator
{
    public function __construct(
        protected MaxMindCountryResolver $maxMindCountryResolver,
    ) {}

    /**
     * Common reverse-proxy and edge headers that expose a visitor country code.
     *
     * @var array<int, string>
     */
    protected const DEFAULT_HEADERS = [
        'CF-IPCountry',
        'CloudFront-Viewer-Country',
        'X-AppEngine-Country',
        'X-Country-Code',
        'Fly-Client-Country',
        'X-Vercel-IP-Country',
        'X-Geo-Country',
    ];

    /**
     * Resolve a two-letter ISO country code without external geo services.
     *
     * Resolution order:
     * 1. Trusted edge/proxy headers (Cloudflare, CloudFront, App Engine, etc.)
     * 2. MaxMind GeoIP database lookup against the client IP (never stored)
     * 3. Optional PHP geoip extension lookup against the client IP (never stored)
     * 4. Local-only dev fallback from config (for Valet / localhost testing)
     */
    public function countryCode(?Request $request): ?string
    {
        if ($request === null) {
            return null;
        }

        foreach ($this->countryHeaders() as $header) {
            $value = $this->normalizeCountryCode((string) $request->headers->get($header, ''));

            if ($value !== null) {
                return $value;
            }
        }

        $fromMaxMind = $this->maxMindCountryResolver->countryCode($request->ip());

        if ($fromMaxMind !== null) {
            return $fromMaxMind;
        }

        $fromGeoIp = $this->countryFromGeoIpExtension($request->ip());

        if ($fromGeoIp !== null) {
            return $fromGeoIp;
        }

        return $this->devCountryCode();
    }

    /**
     * @return array<int, string>
     */
    protected function countryHeaders(): array
    {
        /** @var array<int, string> $custom */
        $custom = config('orbit.analytics.country_headers', []);

        return array_values(array_unique([...self::DEFAULT_HEADERS, ...$custom]));
    }

    protected function normalizeCountryCode(string $value): ?string
    {
        $value = strtoupper(trim($value));

        if ($value === '' || $value === 'XX' || $value === 'T1') {
            return null;
        }

        return preg_match('/^[A-Z]{2}$/', $value) === 1 ? $value : null;
    }

    protected function countryFromGeoIpExtension(?string $ipAddress): ?string
    {
        if (! function_exists('geoip_country_code_by_name') || $ipAddress === null || $ipAddress === '') {
            return null;
        }

        if (! filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        $code = geoip_country_code_by_name($ipAddress);

        return is_string($code) ? $this->normalizeCountryCode($code) : null;
    }

    protected function devCountryCode(): ?string
    {
        if (! app()->environment('local')) {
            return null;
        }

        $code = config('orbit.analytics.dev_country_code');

        return is_string($code) ? $this->normalizeCountryCode($code) : null;
    }
}
