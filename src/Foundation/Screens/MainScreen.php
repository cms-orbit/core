<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Screens;

use CmsOrbit\Core\Activity\Models\OrbitActivity;
use CmsOrbit\Core\Analytics\AnalyticsDashboard;
use CmsOrbit\Core\Foundation\Models\User;
use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Screen;
use CmsOrbit\Core\Screen\TD;
use CmsOrbit\Core\Support\Facades\Layout as LayoutFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\CarbonInterface;
use Illuminate\Support\Facades\Schema;

/**
 * Default landing screen for the admin panel. The package ships a minimal
 * overview so the dashboard renders out of the box; host applications can swap
 * the `orbit.index` config route to point at their own screen.
 */
class MainScreen extends Screen
{
    public function name(): ?string
    {
        return __('Dashboard');
    }

    public function description(): ?string
    {
        return __('Welcome to the Orbit admin panel.');
    }

    public function permission(): ?iterable
    {
        return ['orbit.index'];
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        $analytics = app(AnalyticsDashboard::class)->overview($this->instanceId());

        return [
            'analytics'      => $analytics,
            'recentActivity' => $this->recentActivity(),
            'metrics'        => $this->userMetrics(),
        ];
    }

    /**
     * @return Layout[]
     */
    public function layout(): array
    {
        return [
            LayoutFactory::metrics([
                __('Users')                 => 'metrics.users',
                __('New Users (7 Days)')    => 'metrics.newUsers7d',
                __('Pageviews Today')       => 'analytics.metrics.pageviews',
                __('Unique Visitors Today') => 'analytics.metrics.visitors',
            ])->title(__('Overview')),

            LayoutFactory::columns([
                LayoutFactory::chart('analytics.trends', __('Recent Traffic'))
                    ->type('line')
                    ->description(__('Last 14 days of pageviews and unique visitors.')),

                LayoutFactory::chart('analytics.browsers', __('Browser Breakdown'))
                    ->type('pie')
                    ->description(__('Pageview share by browser family over the last 30 days.')),
            ]),

            LayoutFactory::columns([
                LayoutFactory::table('analytics.topPages', [
                    TD::make('label', __('Page / Route'))->cantHide(),
                    TD::make('views', __('Pageviews'))->alignRight()->cantHide(),
                    TD::make('visitors', __('Visitors'))->alignRight()->cantHide(),
                ])->title(__('Top Pages')),

                LayoutFactory::table('analytics.topReferrers', [
                    TD::make('label', __('Referrer'))->cantHide(),
                    TD::make('visits', __('Visits'))->alignRight()->cantHide(),
                ])->title(__('Top Referrers')),
            ]),

            LayoutFactory::table('recentActivity', [
                TD::make('created_at', __('Occurred'))
                    ->cantHide()
                    ->render(fn (Model $model) => $this->renderTimestamp($model->getAttribute('created_at'))),
                TD::make('description', __('Activity'))
                    ->cantHide()
                    ->render(fn (Model $model) => e((string) ($model->getAttribute('description') ?? '—'))),
                TD::make('causer_label', __('Actor'))
                    ->render(fn (Model $model) => e((string) ($model->getAttribute('causer_label') ?? 'System'))),
                TD::make('subject_label', __('Subject'))
                    ->render(fn (Model $model) => e((string) ($model->getAttribute('subject_label') ?? '—'))),
            ])->title(__('Recent Activity')),
        ];
    }

    protected function instanceId(): ?int
    {
        if (! function_exists('instance_context')) {
            return null;
        }

        return instance_context()?->instance->getKey();
    }

    protected function recentActivity(): mixed
    {
        if (! $this->activitiesTableExists()) {
            return collect();
        }

        return OrbitActivity::query()
            ->forInstance($this->instanceId())
            ->latest('created_at')
            ->limit(8)
            ->get();
    }

    protected function activitiesTableExists(): bool
    {
        return once(fn () => Schema::hasTable('orbit_activities'));
    }

    /**
     * @return array<string, array<string, int|float>>
     */
    protected function userMetrics(): array
    {
        $now = now();
        $sevenDaysAgo = $now->subDays(7);
        $fourteenDaysAgo = $now->subDays(14);

        $totalUsers = User::query()->count();
        $newUsersLast7Days = User::query()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->count();
        $previous7DayUsers = User::query()
            ->where('created_at', '>=', $fourteenDaysAgo)
            ->where('created_at', '<', $sevenDaysAgo)
            ->count();

        return [
            'users' => [
                'value' => $totalUsers,
            ],
            'newUsers7d' => [
                'value' => $newUsersLast7Days,
                'diff'  => $this->percentageChange($newUsersLast7Days, $previous7DayUsers),
            ],
        ];
    }

    protected function percentageChange(int $current, int $previous): ?int
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : null;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

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
}
