<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Analytics;

use Carbon\CarbonInterface;
use CmsOrbit\Core\Analytics\Models\AnalyticsPageview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class AnalyticsDashboard
{
    /**
     * @return array<string, mixed>
     */
    public function overview(?int $instanceId = null): array
    {
        if (! $this->analyticsTableExists()) {
            return $this->disabledPayload(__('Unavailable'));
        }

        if (! $this->setting('analytics.enabled', true, $instanceId)) {
            return $this->disabledPayload(__('Disabled'));
        }

        $today = today();
        $yesterday = today()->subDay();
        $trendStart = today()->subDays(13);
        $summaryStart = today()->subDays(29);
        $baseQuery = $this->baseQuery($instanceId);

        $pageviewsToday = (clone $baseQuery)
            ->whereDate('visited_on', $today)
            ->count();

        $pageviewsYesterday = (clone $baseQuery)
            ->whereDate('visited_on', $yesterday)
            ->count();

        $visitsToday = $this->distinctCount(
            (clone $baseQuery)->whereDate('visited_on', $today),
            'visit_token',
        );

        $visitsYesterday = $this->distinctCount(
            (clone $baseQuery)->whereDate('visited_on', $yesterday),
            'visit_token',
        );

        $visitorsToday = $this->distinctCount(
            (clone $baseQuery)->whereDate('visited_on', $today),
            'visitor_hash',
        );

        $visitorsYesterday = $this->distinctCount(
            (clone $baseQuery)->whereDate('visited_on', $yesterday),
            'visitor_hash',
        );

        return [
            'metrics' => [
                'status'    => ['value' => __('Enabled')],
                'pageviews' => [
                    'value' => number_format($pageviewsToday),
                    'diff'  => $this->diff($pageviewsToday, $pageviewsYesterday),
                ],
                'visits' => [
                    'value' => number_format($visitsToday),
                    'diff'  => $this->diff($visitsToday, $visitsYesterday),
                ],
                'visitors' => [
                    'value' => number_format($visitorsToday),
                    'diff'  => $this->diff($visitorsToday, $visitorsYesterday),
                ],
            ],
            'trends'       => $this->buildTrendDatasets($baseQuery, $trendStart, $today),
            'browsers'     => $this->buildBrowserDatasets($baseQuery, $summaryStart, $today),
            'topPages'     => $this->topPages($baseQuery, $summaryStart, $today),
            'topReferrers' => $this->topReferrers($baseQuery, $summaryStart, $today),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function disabledPayload(string $status): array
    {
        return [
            'metrics' => [
                'status'    => ['value' => $status],
                'pageviews' => ['value' => 0, 'diff' => null],
                'visits'    => ['value' => 0, 'diff' => null],
                'visitors'  => ['value' => 0, 'diff' => null],
            ],
            'trends'       => [],
            'browsers'     => [],
            'topPages'     => [],
            'topReferrers' => [],
        ];
    }

    protected function baseQuery(?int $instanceId): Builder
    {
        return AnalyticsPageview::query()
            ->when($instanceId !== null, fn (Builder $query) => $query->where('instance_id', $instanceId));
    }

    /**
     * @return array<int, array{name: string, labels: array<int, string>, values: array<int, int>}>
     */
    protected function buildTrendDatasets(Builder $baseQuery, CarbonInterface $start, CarbonInterface $end): array
    {
        $rows = (clone $baseQuery)
            ->whereDate('visited_on', '>=', $start->toDateString())
            ->whereDate('visited_on', '<=', $end->toDateString())
            ->selectRaw('DATE(visited_on) as day, count(*) as pageviews, count(distinct visitor_hash) as visitors')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $pageviews = [];
        $visitors = [];

        $day = $start->copy();

        while ($day->lte($end)) {
            $label = $day->format('m/d');
            $key = $day->toDateString();
            $row = $rows->get($key);

            $labels[] = $label;
            $pageviews[] = (int) ($row->pageviews ?? 0);
            $visitors[] = (int) ($row->visitors ?? 0);

            $day = $day->addDay();
        }

        return [
            ['name' => __('Pageviews'), 'labels' => $labels, 'values' => $pageviews],
            ['name' => __('Unique Visitors'), 'labels' => $labels, 'values' => $visitors],
        ];
    }

    /**
     * @return array<int, array{name: string, labels: array<int, string>, values: array<int, int>}>
     */
    protected function buildBrowserDatasets(Builder $baseQuery, CarbonInterface $start, CarbonInterface $end): array
    {
        $rows = (clone $baseQuery)
            ->whereDate('visited_on', '>=', $start->toDateString())
            ->whereDate('visited_on', '<=', $end->toDateString())
            ->whereNotNull('browser_family')
            ->selectRaw('browser_family, count(*) as pageviews')
            ->groupBy('browser_family')
            ->orderByDesc('pageviews')
            ->limit(6)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return [[
            'name'   => __('Pageviews'),
            'labels' => $rows->pluck('browser_family')->map(fn ($value) => (string) $value)->all(),
            'values' => $rows->pluck('pageviews')->map(fn ($value) => (int) $value)->all(),
        ]];
    }

    /**
     * @return array<int, array{label: string, views: string, visitors: string}>
     */
    protected function topPages(Builder $baseQuery, CarbonInterface $start, CarbonInterface $end): array
    {
        return (clone $baseQuery)
            ->whereDate('visited_on', '>=', $start->toDateString())
            ->whereDate('visited_on', '<=', $end->toDateString())
            ->selectRaw('route_uri, page_path, count(*) as views, count(distinct visitor_hash) as visitors')
            ->groupBy('route_uri', 'page_path')
            ->orderByDesc('views')
            ->limit(7)
            ->get()
            ->map(fn ($row) => [
                'label'    => (string) ($row->route_uri ?: $row->page_path ?: '/'),
                'views'    => number_format((int) $row->views),
                'visitors' => number_format((int) $row->visitors),
            ])
            ->all();
    }

    /**
     * @return array<int, array{label: string, visits: string}>
     */
    protected function topReferrers(Builder $baseQuery, CarbonInterface $start, CarbonInterface $end): array
    {
        return (clone $baseQuery)
            ->whereDate('visited_on', '>=', $start->toDateString())
            ->whereDate('visited_on', '<=', $end->toDateString())
            ->whereNotNull('referrer_host')
            ->where('referrer_host', '!=', '')
            ->selectRaw('referrer_host, count(distinct visit_token) as visits')
            ->groupBy('referrer_host')
            ->orderByDesc('visits')
            ->limit(7)
            ->get()
            ->map(fn ($row) => [
                'label'  => (string) $row->referrer_host,
                'visits' => number_format((int) $row->visits),
            ])
            ->all();
    }

    protected function distinctCount(Builder $query, string $column): int
    {
        return (int) $query->distinct()->count($column);
    }

    protected function diff(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    protected function setting(string $key, mixed $default, ?int $instanceId = null): mixed
    {
        if (! $this->configTableExists()) {
            return $default;
        }

        return orbit_config($key, $default, $instanceId);
    }

    protected function analyticsTableExists(): bool
    {
        return Schema::hasTable('orbit_analytics_pageviews');
    }

    protected function configTableExists(): bool
    {
        return Schema::hasTable('orbit_configs');
    }
}
