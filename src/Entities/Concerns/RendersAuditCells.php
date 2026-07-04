<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Support\CarbonInterface;

trait RendersAuditCells
{
    protected function renderTimestamp(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        $date = $value instanceof CarbonInterface ? $value : Carbon::parse($value);
        $absolute = e($date->translatedFormat('Y.m.d H:i'));
        $relative = e($date->diffForHumans());
        $iso = e($date->toIso8601String());

        return <<<HTML
<time datetime="{$iso}" class="inline-flex flex-col leading-tight">
    <span>{$absolute}</span>
    <span class="text-xs text-gray-500">{$relative}</span>
</time>
HTML;
    }

    protected function renderBadge(string $label, string $tone = 'slate'): string
    {
        $palette = match ($tone) {
            'green'  => 'bg-green-500/10 text-green-700 dark:text-green-300',
            'red'    => 'bg-red-500/10 text-red-700 dark:text-red-300',
            'amber'  => 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
            'blue'   => 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
            'purple' => 'bg-purple-500/10 text-purple-700 dark:text-purple-300',
            default  => 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
        };

        return sprintf(
            '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium %s">%s</span>',
            $palette,
            e($label),
        );
    }

    protected function renderCodeValue(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return sprintf(
            '<code class="inline-flex rounded bg-slate-100 px-1.5 py-0.5 text-xs dark:bg-slate-800">%s</code>',
            e($value),
        );
    }

    /**
     * @param array<string, mixed>|null $value
     */
    protected function renderProperties(?array $value): string
    {
        if ($value === null || $value === []) {
            return '—';
        }

        return sprintf(
            '<pre class="max-h-96 overflow-auto rounded-lg bg-slate-950/95 p-3 text-xs leading-relaxed text-slate-100">%s</pre>',
            e(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
        );
    }
}
