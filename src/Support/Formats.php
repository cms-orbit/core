<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class Formats
{
    /**
     * Convert a UNIX timestamp to a formatted datetime string.
     *
     * @param int $time UNIX timestamp
     *
     * @return string Formatted Datetime string
     */
    public static function toDateTimeString(int $time): string
    {
        return Carbon::createFromTimestamp($time)->toDateTimeString();
    }

    /**
     * Format bytes to KB, MB, GB, TB.
     */
    public static function formatBytes(int $size, int $precision = 2): string
    {
        if ($size <= 0) {
            return (string) $size;
        }

        $base = log($size) / log(1024);
        $suffixes = [' bytes', ' KB', ' MB', ' GB', ' TB'];

        return round(1024 ** ($base - floor($base)), $precision).$suffixes[(int) floor($base)];
    }

    /**
     * Format a datetime for table cells: relative within 24 hours, otherwise Y-m-d (H:i).
     */
    public static function formatDateTimeForTable(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);
        $iso = e($date->toIso8601String());

        if ($date->greaterThan(now()->subDay())) {
            $display = e($date->diffForHumans(['parts' => 1, 'short' => false]));
        } else {
            $display = e($date->format('Y-m-d (H:i)'));
        }

        return <<<HTML
<time datetime="{$iso}" class="whitespace-nowrap tabular-nums">{$display}</time>
HTML;
    }
}
