<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities;

use CmsOrbit\Core\Analytics\Filters\VisitorRecordTableFilter;
use CmsOrbit\Core\Analytics\Models\AnalyticsPageview;
use CmsOrbit\Core\Entities\Concerns\RendersAuditCells;
use CmsOrbit\Core\Entities\Screens\VisitorRecordListScreen;
use CmsOrbit\Core\Entities\Screens\VisitorRecordViewScreen;
use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Screen\Sight;
use CmsOrbit\Core\Screen\TD;
use CmsOrbit\Core\Support\Formats;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class VisitorRecordEntity extends Entity
{
    use RendersAuditCells;

    public function model(): string
    {
        return AnalyticsPageview::class;
    }

    public function query(): Builder
    {
        return $this->applyVisibilityScope(parent::query())
            ->latest('created_at');
    }

    /**
     * @return array<string, class-string>
     */
    public function screens(): array
    {
        return [
            'list' => VisitorRecordListScreen::class,
            'view' => VisitorRecordViewScreen::class,
        ];
    }

    public function label(): string
    {
        return __('Visitor Records');
    }

    public function singularLabel(): string
    {
        return __('Visitor Record');
    }

    public function icon(): string
    {
        return 'bs.people';
    }

    public function section(): string
    {
        return __('Users & Roles');
    }

    public function sectionKey(): string
    {
        return 'access-control';
    }

    public function sort(): int
    {
        return 1400;
    }

    public function menuParent(): ?string
    {
        return 'access-control-records';
    }

    public function crud(): array
    {
        return ['list', 'view'];
    }

    public function perPage(): int
    {
        return 25;
    }

    /**
     * @return array<int, class-string>
     */
    public function filters(): array
    {
        return [
            VisitorRecordTableFilter::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function searchColumns(): array
    {
        return [
            'page_path',
            'route_uri',
            'route_name',
            'referrer_host',
            'user_email',
            'user_name',
        ];
    }

    public function fields(): array
    {
        return [];
    }

    public function columns(): array
    {
        return [
            TD::make('created_at', __('Visited'))
                ->sort()
                ->filter(TD::FILTER_DATE_RANGE)
                ->render(fn (Model $model) => $this->renderTimestamp($model->getAttribute('created_at'))),
            TD::make('user_id', __('User'))
                ->filter(TD::FILTER_SELECT, $this->userFilterOptions())
                ->filterInline()
                ->render(fn (Model $model) => $this->renderUser($model)),
            TD::make('visitor_hash', __('Visitor'))
                ->filter(TD::FILTER_TEXT)
                ->render(fn (Model $model) => $this->renderVisitorLink($model)),
            TD::make('page_path', __('Page'))
                ->sort()
                ->filter(TD::FILTER_TEXT)
                ->render(fn (Model $model) => $this->renderPageCell($model)),
            TD::make('device_type', __('Device'))
                ->sort()
                ->filter(TD::FILTER_SELECT, $this->deviceFilterOptions())
                ->render(fn (Model $model) => $this->renderBadge(
                    Formats::deviceTypeLabel($model->getAttribute('device_type')),
                    'blue',
                )),
            TD::make('referrer_host', __('Referrer'))
                ->filter(TD::FILTER_TEXT)
                ->render(fn (Model $model) => e((string) ($model->getAttribute('referrer_host') ?? __('Direct')))),
            TD::make('country_code', __('Country'))
                ->sort()
                ->filter(TD::FILTER_SELECT, $this->countryFilterOptions())
                ->render(fn (Model $model) => $this->renderCountry($model)),
            TD::make('instance_id', __('Scope'))
                ->defaultHidden()
                ->render(fn (Model $model) => $this->renderScope($model)),
            TD::make('route_name', __('Route Name'))
                ->defaultHidden()
                ->filter(TD::FILTER_TEXT)
                ->render(fn (Model $model) => e((string) ($model->getAttribute('route_name') ?? '—'))),
            TD::make('browser_family', __('Browser'))
                ->sort()
                ->defaultHidden()
                ->filter(TD::FILTER_SELECT, $this->browserFilterOptions())
                ->render(fn (Model $model) => e((string) ($model->getAttribute('browser_family') ?? __('Unknown')))),
            TD::make('ip_address', __('IP'))
                ->defaultHidden()
                ->filter(TD::FILTER_TEXT)
                ->render(fn (Model $model) => $this->renderCodeValue($model->getAttribute('ip_address'))),
        ];
    }

    public function queryForVisitorHash(string $visitorHash): Builder
    {
        return $this->applyVisibilityScope(
            AnalyticsPageview::query()->where('visitor_hash', $visitorHash)
        );
    }

    public function relatedVisitsQuery(Model $model): Builder
    {
        return $this->queryForVisitorHash((string) $model->getAttribute('visitor_hash'))
            ->whereKeyNot($model->getKey())
            ->latest('created_at');
    }

    /**
     * @return array{total_visits: int, other_visits: int, first_seen: ?string, last_seen: ?string}
     */
    public function visitorSummary(Model $model): array
    {
        $visitorHash = (string) $model->getAttribute('visitor_hash');
        $query = $this->queryForVisitorHash($visitorHash);

        $totalVisits = (clone $query)->count();
        $firstSeen = (clone $query)->oldest('created_at')->value('created_at');
        $lastSeen = (clone $query)->latest('created_at')->value('created_at');

        return [
            'total_visits' => $totalVisits,
            'other_visits' => max(0, $totalVisits - 1),
            'first_seen'   => $this->formatSummaryTimestamp($firstSeen),
            'last_seen'    => $this->formatSummaryTimestamp($lastSeen),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function viewDetailProps(Model $model): array
    {
        return [
            'page_path'      => (string) $model->getAttribute('page_path'),
            'route_name'     => $model->getAttribute('route_name'),
            'route_uri'      => $model->getAttribute('route_uri'),
            'browser_family' => (string) ($model->getAttribute('browser_family') ?? __('Unknown')),
            'device_type'    => (string) ($model->getAttribute('device_type') ?? 'unknown'),
            'user_agent'     => (string) ($model->getAttribute('user_agent') ?? ''),
            'visitor_hash'   => (string) $model->getAttribute('visitor_hash'),
            'summary'        => $this->visitorSummary($model),
            'visit_rows'     => [
                [
                    'label' => __('Visited'),
                    'html'  => $this->renderTimestamp($model->getAttribute('created_at')),
                ],
                [
                    'label' => __('Scope'),
                    'html'  => $this->renderScope($model),
                ],
                [
                    'label' => __('Page'),
                    'value' => (string) $model->getAttribute('page_path'),
                ],
                [
                    'label' => __('Route Name'),
                    'value' => (string) ($model->getAttribute('route_name') ?? '—'),
                ],
                [
                    'label' => __('Route URI'),
                    'value' => (string) ($model->getAttribute('route_uri') ?? '—'),
                ],
                [
                    'label' => __('Referrer'),
                    'value' => (string) ($model->getAttribute('referrer_host') ?? __('Direct')),
                ],
                [
                    'label' => __('Entrance'),
                    'html'  => $this->renderBadge(
                        $model->getAttribute('is_entrance') ? __('Yes') : __('No'),
                        $model->getAttribute('is_entrance') ? 'green' : 'slate',
                    ),
                ],
            ],
            'visitor_rows' => [
                [
                    'label' => __('User'),
                    'html'  => $this->renderUser($model),
                ],
                [
                    'label' => __('Visitor Hash'),
                    'html'  => $this->renderCodeValue((string) $model->getAttribute('visitor_hash')),
                ],
                [
                    'label' => __('Visit Token'),
                    'html'  => $this->renderCodeValue((string) $model->getAttribute('visit_token')),
                ],
                [
                    'label' => __('Browser'),
                    'value' => (string) ($model->getAttribute('browser_family') ?? __('Unknown')),
                ],
                [
                    'label' => __('Device'),
                    'html'  => $this->renderBadge(
                        Formats::deviceTypeLabel($model->getAttribute('device_type')),
                        'blue',
                    ),
                ],
                [
                    'label' => __('Bot'),
                    'html'  => $this->renderBadge(
                        $model->getAttribute('is_bot') ? __('Yes') : __('No'),
                        $model->getAttribute('is_bot') ? 'amber' : 'slate',
                    ),
                ],
            ],
            'network_rows' => [
                [
                    'label' => __('IP'),
                    'html'  => $this->renderCodeValue($model->getAttribute('ip_address')),
                ],
                [
                    'label' => __('Country'),
                    'html'  => $this->renderCountry($model),
                ],
            ],
        ];
    }

    /**
     * @return TD[]
     */
    public function historyColumns(): array
    {
        $key = static::uriKey();

        return [
            TD::make('created_at', __('Visited'))
                ->render(fn (Model $model) => $this->renderTimestamp($model->getAttribute('created_at'))),
            TD::make('page_path', __('Page'))
                ->render(fn (Model $model) => $this->renderPageCell($model)),
            TD::make('device_type', __('Device'))
                ->render(fn (Model $model) => $this->renderBadge(
                    Formats::deviceTypeLabel($model->getAttribute('device_type')),
                    'blue',
                )),
            TD::make('referrer_host', __('Referrer'))
                ->render(fn (Model $model) => e((string) ($model->getAttribute('referrer_host') ?? __('Direct')))),
            TD::make(__('Actions'))
                ->alignRight()
                ->render(fn (Model $model) => sprintf(
                    '<a href="%s" class="inline-flex items-center gap-1 text-xs font-medium text-orbit-primary hover:underline">%s</a>',
                    e(route('orbit.entities.'.$key.'.view', ['id' => $model->getKey()])),
                    e(__('View')),
                )),
        ];
    }

    public function legend(): array
    {
        return [
            Sight::make('created_at', __('Visited'))
                ->render(fn (Model $model) => $this->renderTimestamp($model->getAttribute('created_at'))),
            Sight::make('instance_id', __('Scope'))
                ->render(fn (Model $model) => $this->renderScope($model)),
            Sight::make('user_id', __('User'))
                ->render(fn (Model $model) => $this->renderUser($model)),
            Sight::make('visitor_hash', __('Visitor Hash'))
                ->render(fn (Model $model) => $this->renderCodeValue(substr((string) $model->getAttribute('visitor_hash'), 0, 20))),
            Sight::make('page_path', __('Page')),
            Sight::make('route_name', __('Route Name')),
            Sight::make('route_uri', __('Route URI')),
            Sight::make('referrer_host', __('Referrer')),
            Sight::make('browser_family', __('Browser')),
            Sight::make('device_type', __('Device')),
            Sight::make('country_code', __('Country'))
                ->render(fn (Model $model) => $this->renderCountry($model)),
            Sight::make('ip_address', __('IP'))
                ->render(fn (Model $model) => $this->renderCodeValue($model->getAttribute('ip_address'))),
            Sight::make('visit_token', __('Visit Token'))
                ->render(fn (Model $model) => $this->renderCodeValue(substr((string) $model->getAttribute('visit_token'), 0, 20))),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function browserFilterOptions(): array
    {
        return $this->applyVisibilityScope(AnalyticsPageview::query())
            ->whereNotNull('browser_family')
            ->distinct()
            ->orderBy('browser_family')
            ->pluck('browser_family', 'browser_family')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function deviceFilterOptions(): array
    {
        return $this->applyVisibilityScope(AnalyticsPageview::query())
            ->whereNotNull('device_type')
            ->distinct()
            ->orderBy('device_type')
            ->pluck('device_type', 'device_type')
            ->mapWithKeys(fn (string $type, string $key): array => [
                $key => Formats::deviceTypeLabel($type),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function countryFilterOptions(): array
    {
        return $this->applyVisibilityScope(AnalyticsPageview::query())
            ->whereNotNull('country_code')
            ->distinct()
            ->orderBy('country_code')
            ->pluck('country_code', 'country_code')
            ->mapWithKeys(fn (string $code, string $key) => [$key => strtoupper($code)])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function userFilterOptions(): array
    {
        $modelClass = config('auth.providers.users.model');

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return [];
        }

        return $modelClass::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(fn (Model $user) => [
                (string) $user->getKey() => sprintf(
                    '%s (%s)',
                    (string) $user->getAttribute('name'),
                    (string) $user->getAttribute('email'),
                ),
            ])
            ->all();
    }

    protected function renderUser(Model $model): string
    {
        $email = $model->getAttribute('user_email');
        $name = $model->getAttribute('user_name');

        if (! is_string($email) || blank($email)) {
            return '<span class="text-sm text-gray-400">'.e(__('Guest')).'</span>';
        }

        if (is_string($name) && filled($name)) {
            return e(sprintf('%s (%s)', $name, $email));
        }

        return e($email);
    }

    protected function renderScope(Model $model): string
    {
        return $model->getAttribute('instance_id') === null
            ? $this->renderBadge((string) __('Host'), 'blue')
            : $this->renderBadge((string) __('Instance'), 'green');
    }

    protected function renderVisitorLink(Model $model): string
    {
        $hash = (string) $model->getAttribute('visitor_hash');

        if ($hash === '') {
            return '—';
        }

        $short = substr($hash, 0, 12);
        $url = route('orbit.entities.'.static::uriKey().'.index', [
            'filter' => [
                'visitor_hash' => $hash,
            ],
        ]);

        return sprintf(
            '<a href="%s" class="font-mono text-xs text-gray-600 underline-offset-2 hover:text-orbit-primary-600 hover:underline dark:text-gray-300" title="%s">%s</a>',
            e($url),
            e($hash),
            e($short),
        );
    }

    protected function renderPageCell(Model $model): string
    {
        $path = e((string) $model->getAttribute('page_path'));

        return sprintf('<span class="font-medium">%s</span>', $path);
    }

    protected function renderCountry(Model $model): string
    {
        $code = $model->getAttribute('country_code');

        if (! is_string($code) || blank($code)) {
            return '<span class="text-sm text-gray-400">'.e(__('Unknown')).'</span>';
        }

        return $this->renderBadge(strtoupper($code), 'purple');
    }

    protected function applyVisibilityScope(Builder $query): Builder
    {
        $instanceId = $this->instanceId();

        if ($instanceId === null) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($instanceId) {
            $builder
                ->where('instance_id', $instanceId)
                ->orWhereNull('instance_id');
        });
    }

    protected function instanceId(): ?int
    {
        if (! function_exists('instance_context')) {
            return null;
        }

        return instance_context()?->instance->getKey();
    }

    protected function formatSummaryTimestamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $date = $value instanceof Carbon ? $value : Carbon::parse((string) $value);

        return $date->diffForHumans();
    }
}
