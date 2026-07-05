<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Analytics;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Throwable;

class MaxMindCountryResolver
{
    private ?Reader $reader = null;

    public function countryCode(?string $ipAddress): ?string
    {
        if (! $this->isEnabled() || $ipAddress === null || $ipAddress === '') {
            return null;
        }

        $databasePath = $this->databasePath();

        if ($databasePath === null) {
            return null;
        }

        if (! filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        try {
            $record = $this->reader($databasePath)->country($ipAddress);
            $code = $record->country->isoCode;

            return is_string($code) ? $this->normalizeCountryCode($code) : null;
        } catch (AddressNotFoundException) {
            return null;
        } catch (Throwable) {
            return null;
        }
    }

    public function isEnabled(): bool
    {
        return (bool) config('orbit.analytics.geoip.enabled', false);
    }

    public function databasePath(): ?string
    {
        $path = config('orbit.analytics.geoip.database_path');

        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            return null;
        }

        return $path;
    }

    protected function normalizeCountryCode(string $value): ?string
    {
        $value = strtoupper(trim($value));

        if ($value === '' || $value === 'XX' || $value === 'T1') {
            return null;
        }

        return preg_match('/^[A-Z]{2}$/', $value) === 1 ? $value : null;
    }

    protected function reader(string $databasePath): Reader
    {
        if ($this->reader === null) {
            $this->reader = new Reader($databasePath);
        }

        return $this->reader;
    }
}
