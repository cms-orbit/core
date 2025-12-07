<?php

declare(strict_types=1);

use CmsOrbit\Core\Support\Alert\Alert;
use CmsOrbit\Core\Foundation\Filters\HttpFilter;
use CmsOrbit\Core\Support\Color;

if (! function_exists('alert')) {
    /**
     * Helper function to send an alert.
     */
    function alert(?string $message = null, Color $color = Color::INFO): Alert
    {
        if (!function_exists('app') || !app()->bound(Alert::class)) {
            throw new \RuntimeException('Application is not bootstrapped.');
        }

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
        if (!function_exists('app') || !app()->bound('request')) {
            return false;
        }
        try {
            return (new HttpFilter)->isSort($property);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('get_sort')) {
    function get_sort(?string $property): string
    {
        if (!function_exists('app') || !app()->bound('request')) {
            return '';
        }
        try {
            return (new HttpFilter)->getSort($property);
        } catch (\Throwable $e) {
            return '';
        }
    }
}

if (! function_exists('get_filter')) {
    /**
     * @return string|array|null
     */
    function get_filter(string $property)
    {
        if (!function_exists('app') || !app()->bound('request')) {
            return null;
        }
        try {
            return (new HttpFilter)->getFilter($property);
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (! function_exists('get_filter_string')) {
    /**
     * @return string
     */
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
        if (!function_exists('app') || !app()->bound('request')) {
            return $property;
        }
        try {
            return (new HttpFilter)->revertSort($property);
        } catch (\Throwable $e) {
            return $property;
        }
    }
}
