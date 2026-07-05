<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Analytics;

use CmsOrbit\Core\Analytics\Models\AnalyticsPageview;
use CmsOrbit\Core\Support\Formats;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

class VisitorRecordInsights
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?int $instanceId, int $days = 30): array
    {
        $start = today()->subDays($days - 1);
        $baseQuery = $this->scopedQuery($instanceId)
            ->whereDate('visited_on', '>=', $start->toDateString())
            ->whereDate('visited_on', '<=', today()->toDateString());

        $pageviews = (clone $baseQuery)->count();
        $visitors = $this->distinctCount(clone $baseQuery, 'visitor_hash');
        $signedIn = (clone $baseQuery)->whereNotNull('user_id')->count();
        $guests = (clone $baseQuery)->whereNull('user_id')->count();
        $signedInShare = $pageviews > 0 ? round(($signedIn / $pageviews) * 100, 1) : 0.0;

        $topPages = $this->topPages(clone $baseQuery);
        $devices = $this->rankedBreakdown(clone $baseQuery, 'device_type');
        $referrers = $this->rankedBreakdown(clone $baseQuery, 'referrer_host', distinctVisit: true);
        $countries = $this->rankedBreakdown(clone $baseQuery, 'country_code');

        $topPage = $topPages[0] ?? null;
        $topDevice = $devices[0] ?? null;
        $topReferrer = $referrers[0] ?? null;
        $topCountry = $countries[0] ?? null;

        return [
            'metrics' => [
                'pageviews' => [
                    'value'  => number_format($pageviews),
                    'detail' => sprintf(
                        '%s %s · %s%% %s',
                        number_format($visitors),
                        __('unique visitors'),
                        number_format($signedInShare, 1),
                        __('signed in'),
                    ),
                    'detailTone' => 'green',
                ],
                'topPage' => [
                    'value'  => (string) ($topPage['label'] ?? '—'),
                    'detail' => $topPage !== null
                        ? sprintf('%s %s · %s %s', $topPage['views'], __('views'), $topPage['visitors'], __('visitors'))
                        : __('No pageviews yet'),
                    'detailTone' => 'blue',
                ],
                'topReferrer' => [
                    'value'  => (string) ($topReferrer['label'] ?? __('Direct')),
                    'detail' => $topReferrer !== null
                        ? sprintf('%s %s · %s%% %s', $topReferrer['count'], __('visits'), $topReferrer['share'], __('share'))
                        : __('No external referrers'),
                    'detailTone' => 'amber',
                ],
                'topDevice' => [
                    'value'      => (string) ($topDevice['label'] ?? '—'),
                    'detail'     => $this->deviceDetailLine($topDevice, $topCountry, $pageviews),
                    'detailTone' => 'blue',
                ],
            ],
            'topPages'  => $topPages,
            'devices'   => $devices,
            'referrers' => $referrers,
            'countries' => $countries,
        ];
    }

    protected function scopedQuery(?int $instanceId): Builder
    {
        return AnalyticsPageview::query()
            ->when(
                $instanceId !== null,
                fn (Builder $query) => $query->where(function (Builder $builder) use ($instanceId) {
                    $builder
                        ->where('instance_id', $instanceId)
                        ->orWhereNull('instance_id');
                }),
            );
    }

    /**
     * @return array<int, array{label: string, route_name: ?string, url: string, views: string, visitors: string, views_raw: int}>
     */
    protected function topPages(Builder $query): array
    {
        return $query
            ->selectRaw('route_name, page_path, route_uri, count(*) as views, count(distinct visitor_hash) as visitors')
            ->groupBy('route_name', 'page_path', 'route_uri')
            ->orderByDesc('views')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'label'      => (string) ($row->route_uri ?: $row->page_path ?: '/'),
                'route_name' => is_string($row->route_name) && filled($row->route_name) ? $row->route_name : null,
                'url'        => $this->publicPageUrl((string) ($row->page_path ?: '/'), $row->route_name),
                'views'      => number_format((int) $row->views),
                'visitors'   => number_format((int) $row->visitors),
                'views_raw'  => (int) $row->views,
            ])
            ->all();
    }

    /**
     * @return array<int, array{label: string, count: string, count_raw: int, share: string}>
     */
    protected function rankedBreakdown(Builder $query, string $column, bool $distinctVisit = false): array
    {
        $metric = $distinctVisit ? 'count(distinct visit_token)' : 'count(*)';

        $scoped = (clone $query)
            ->whereNotNull($column)
            ->where($column, '!=', '');

        $grandTotal = $distinctVisit
            ? (int) (clone $scoped)->distinct()->count('visit_token')
            : (int) (clone $scoped)->count();

        return $scoped
            ->selectRaw("{$column} as label, {$metric} as total")
            ->groupBy($column)
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($row) use ($column, $grandTotal) {
                $countRaw = (int) $row->total;
                $share = $grandTotal > 0 ? round(($countRaw / $grandTotal) * 100, 1) : 0.0;

                return [
                    'label'     => match ($column) {
                        'device_type'   => Formats::deviceTypeLabel((string) $row->label),
                        'referrer_host' => (string) $row->label,
                        'country_code'  => strtoupper((string) $row->label),
                        default         => (string) $row->label,
                    },
                    'count'     => number_format($countRaw),
                    'count_raw' => $countRaw,
                    'share'     => number_format($share, 1),
                ];
            })
            ->all();
    }

    /**
     * @param array{label?: string, count?: string, count_raw?: int, share?: string}|null $topDevice
     * @param array{label?: string, count?: string, count_raw?: int, share?: string}|null $topCountry
     */
    protected function deviceDetailLine(?array $topDevice, ?array $topCountry, int $pageviews): string
    {
        if ($topDevice === null) {
            return __('No device data yet');
        }

        $deviceShare = $pageviews > 0
            ? round(((int) ($topDevice['count_raw'] ?? 0) / $pageviews) * 100, 1)
            : 0.0;

        $country = $topCountry['label'] ?? null;

        if (is_string($country) && filled($country)) {
            return sprintf(
                '%s%% %s · %s %s',
                number_format($deviceShare, 1),
                __('of views'),
                __('top country'),
                $country,
            );
        }

        return sprintf('%s %s · %s%% %s', $topDevice['count'] ?? '0', __('views'), number_format($deviceShare, 1), __('share'));
    }

    protected function publicPageUrl(string $pagePath, mixed $routeName): string
    {
        if (is_string($routeName) && filled($routeName) && Route::has($routeName)) {
            return route($routeName);
        }

        $base = rtrim((string) config('app.url'), '/');
        $path = str_starts_with($pagePath, '/') ? $pagePath : '/'.$pagePath;

        return $base.$path;
    }

    protected function distinctCount(Builder $query, string $column): int
    {
        return (int) $query->distinct()->count($column);
    }
}
