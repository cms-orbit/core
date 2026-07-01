<?php

declare(strict_types=1);

use CmsOrbit\Core\Alert\Alert;
use CmsOrbit\Core\Config\ConfigRegistry;
use CmsOrbit\Core\Filters\HttpFilter;
use CmsOrbit\Core\Support\Color;

if (! function_exists('orbit_config')) {
    /**
     * Resolve a stored Orbit configuration value with chain-of-responsibility
     * fallback (instance override → global → dot-notation parent → registered
     * default → supplied default).
     */
    function orbit_config(string $key, mixed $default = null, ?int $instanceId = null): mixed
    {
        return app(ConfigRegistry::class)->get($key, $default, $instanceId);
    }
}

if (! function_exists('alert')) {
    /**
     * Helper function to send an alert.
     */
    function alert(?string $message = null, Color $color = Color::INFO): Alert
    {
        $notifier = app(Alert::class);

        if ($message !== null) {
            return $notifier->message($message, $color);
        }

        return $notifier;
    }
}

if (! function_exists('is_sort')) {
    function is_sort(string $property): bool
    {
        return (new HttpFilter)->isSort($property);
    }
}

if (! function_exists('get_sort')) {
    function get_sort(?string $property): string
    {
        return (new HttpFilter)->getSort($property);
    }
}

if (! function_exists('get_filter')) {
    /**
     * @return string|array|null
     */
    function get_filter(string $property)
    {
        return (new HttpFilter)->getFilter($property);
    }
}

if (! function_exists('get_filter_string')) {
    function get_filter_string(string $property): ?string
    {
        $filter = get_filter($property);

        if (is_array($filter) && (isset($filter['min']) || isset($filter['max']))) {
            return sprintf('%s - %s', $filter['min'] ?? '', $filter['max'] ?? '');
        }

        if (is_array($filter) && (isset($filter['start']) || isset($filter['end']))) {
            return sprintf('%s - %s', $filter['start'] ?? '', $filter['end'] ?? '');
        }

        if (is_array($filter)) {
            return implode(', ', $filter);
        }

        return $filter;
    }
}

if (! function_exists('revert_sort')) {
    function revert_sort(string $property): string
    {
        return (new HttpFilter)->revertSort($property);
    }
}
