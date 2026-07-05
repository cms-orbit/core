<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Filters;

use Illuminate\Support\Arr;

class FilterQuery
{
    /**
     * Normalize list-style filter values to a comma-separated scalar for URLs.
     *
     * @return string|array<string, mixed>
     */
    public static function normalizeValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (isset($value['start']) || isset($value['end']) || isset($value['min']) || isset($value['max'])) {
            return $value;
        }

        return collect(Arr::flatten($value))
            ->filter(static fn ($item) => $item !== null && $item !== '')
            ->unique()
            ->values()
            ->implode(',');
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public static function normalizeQueryParams(array $query): array
    {
        $normalized = collect($query)->except(['page', '_token'])->all();

        if (! isset($normalized['filter']) || ! is_array($normalized['filter'])) {
            return $normalized;
        }

        $normalized['filter'] = collect($normalized['filter'])
            ->map(fn ($value) => static::normalizeValue($value))
            ->all();

        return $normalized;
    }
}
