<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities;

use CmsOrbit\Core\Analytics\Models\AnalyticsPageview;
use CmsOrbit\Core\Entities\Concerns\RendersAuditCells;
use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Screen\Sight;
use CmsOrbit\Core\Screen\TD;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class VisitorRecordEntity extends Entity
{
    use RendersAuditCells;

    public function model(): string
    {
        return AnalyticsPageview::class;
    }

    public function query(): Builder
    {
        return parent::query()
            ->when($this->instanceId() !== null, fn (Builder $query) => $query->where('instance_id', $this->instanceId()))
            ->latest('created_at');
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
        return __('Access Control');
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
                ->filter(TD::FILTER_SELECT, $this->userOptions())
                ->render(fn (Model $model) => $this->renderUser($model)),
            TD::make('page_path', __('Page'))
                ->sort()
                ->render(fn (Model $model) => e((string) $model->getAttribute('page_path'))),
            TD::make('route_name', __('Route Name'))
                ->defaultHidden()
                ->render(fn (Model $model) => e((string) ($model->getAttribute('route_name') ?? '—'))),
            TD::make('browser_family', __('Browser'))
                ->sort()
                ->filter(TD::FILTER_SELECT, $this->browserOptions())
                ->render(fn (Model $model) => e((string) ($model->getAttribute('browser_family') ?? 'Unknown'))),
            TD::make('device_type', __('Device'))
                ->sort()
                ->filter(TD::FILTER_SELECT, $this->deviceOptions())
                ->render(fn (Model $model) => $this->renderBadge(
                    (string) ($model->getAttribute('device_type') ?? 'unknown'),
                    'blue',
                )),
            TD::make('referrer_host', __('Referrer'))
                ->render(fn (Model $model) => e((string) ($model->getAttribute('referrer_host') ?? 'Direct'))),
            TD::make('visitor_hash', __('Visitor'))
                ->defaultHidden()
                ->render(fn (Model $model) => $this->renderCodeValue(substr((string) $model->getAttribute('visitor_hash'), 0, 12))),
        ];
    }

    public function legend(): array
    {
        return [
            Sight::make('created_at', __('Visited'))
                ->render(fn (Model $model) => $this->renderTimestamp($model->getAttribute('created_at'))),
            Sight::make('user_id', __('User'))
                ->render(fn (Model $model) => $this->renderUser($model)),
            Sight::make('page_path', __('Page')),
            Sight::make('route_name', __('Route Name')),
            Sight::make('route_uri', __('Route URI')),
            Sight::make('referrer_host', __('Referrer')),
            Sight::make('browser_family', __('Browser')),
            Sight::make('device_type', __('Device')),
            Sight::make('visit_token', __('Visit Token'))
                ->render(fn (Model $model) => $this->renderCodeValue(substr((string) $model->getAttribute('visit_token'), 0, 20))),
            Sight::make('visitor_hash', __('Visitor Hash'))
                ->render(fn (Model $model) => $this->renderCodeValue(substr((string) $model->getAttribute('visitor_hash'), 0, 20))),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function browserOptions(): array
    {
        return AnalyticsPageview::query()
            ->when($this->instanceId() !== null, fn (Builder $query) => $query->where('instance_id', $this->instanceId()))
            ->whereNotNull('browser_family')
            ->distinct()
            ->orderBy('browser_family')
            ->pluck('browser_family', 'browser_family')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function deviceOptions(): array
    {
        return AnalyticsPageview::query()
            ->when($this->instanceId() !== null, fn (Builder $query) => $query->where('instance_id', $this->instanceId()))
            ->whereNotNull('device_type')
            ->distinct()
            ->orderBy('device_type')
            ->pluck('device_type', 'device_type')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function userOptions(): array
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

    protected function instanceId(): ?int
    {
        if (! function_exists('instance_context')) {
            return null;
        }

        return instance_context()?->instance->getKey();
    }
}
