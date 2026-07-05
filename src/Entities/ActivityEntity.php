<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities;

use CmsOrbit\Core\Activity\Models\OrbitActivity;
use CmsOrbit\Core\Entities\Concerns\RendersAuditCells;
use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Screen\Sight;
use CmsOrbit\Core\Screen\TD;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ActivityEntity extends Entity
{
    use RendersAuditCells;

    public static function uriKey(): string
    {
        return 'activity-logs';
    }

    public function model(): string
    {
        return OrbitActivity::class;
    }

    public function query(): Builder
    {
        return parent::query()
            ->forInstance($this->instanceId())
            ->latest('created_at');
    }

    public function label(): string
    {
        return __('Activity Logs');
    }

    public function singularLabel(): string
    {
        return __('Activity Log');
    }

    public function icon(): string
    {
        return 'bs.clock-history';
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
        return 1200;
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
            TD::make('created_at', __('Occurred'))
                ->sort()
                ->filter(TD::FILTER_DATE_RANGE)
                ->render(fn (Model $model) => $this->renderTimestamp($model->getAttribute('created_at'))),
            TD::make('category', __('Category'))
                ->sort()
                ->filter(TD::FILTER_SELECT, $this->categoryOptions())
                ->render(fn (Model $model) => $this->renderCategory($model)),
            TD::make('event', __('Event'))
                ->sort()
                ->filter(TD::FILTER_SELECT, OrbitActivity::eventOptions())
                ->render(fn (Model $model) => $this->renderEvent($model)),
            TD::make('description', __('Description'))
                ->render(fn (Model $model) => e((string) ($model->getAttribute('description') ?? '—'))),
            TD::make('causer_id', __('Actor'))
                ->filter(TD::FILTER_SELECT, $this->userOptions())
                ->render(fn (Model $model) => e((string) ($model->getAttribute('causer_label') ?? 'System'))),
            TD::make('subject_label', __('Subject'))
                ->render(fn (Model $model) => e((string) ($model->getAttribute('subject_label') ?? '—'))),
        ];
    }

    public function legend(): array
    {
        return [
            Sight::make('created_at', __('Occurred'))
                ->render(fn (Model $model) => $this->renderTimestamp($model->getAttribute('created_at'))),
            Sight::make('category', __('Category'))
                ->render(fn (Model $model) => $this->renderCategory($model)),
            Sight::make('event', __('Event'))
                ->render(fn (Model $model) => $this->renderEvent($model)),
            Sight::make('description', __('Description')),
            Sight::make('causer_label', __('Actor')),
            Sight::make('subject_label', __('Subject')),
            Sight::make('auth_identifier', __('Identifier')),
            Sight::make('ip_address', __('Network')),
            Sight::make('browser_family', __('Browser')),
            Sight::make('device_type', __('Device')),
            Sight::make('properties', __('Details'))
                ->render(fn (Model $model) => $this->renderProperties($model->getAttribute('properties'))),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function categoryOptions(): array
    {
        return [
            OrbitActivity::CATEGORY_MODEL    => __('Model'),
            OrbitActivity::CATEGORY_AUTH     => __('Authentication'),
            OrbitActivity::CATEGORY_SECURITY => __('Security'),
        ];
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

    protected function renderCategory(Model $model): string
    {
        $category = (string) $model->getAttribute('category');

        return $this->renderBadge(
            $this->categoryOptions()[$category] ?? $category,
            match ($category) {
                OrbitActivity::CATEGORY_AUTH     => 'blue',
                OrbitActivity::CATEGORY_SECURITY => 'amber',
                default                          => 'slate',
            },
        );
    }

    protected function renderEvent(Model $model): string
    {
        $event = (string) $model->getAttribute('event');

        return $this->renderBadge(
            OrbitActivity::eventOptions()[$event] ?? $event,
            match ($event) {
                'created', 'restored', 'login_succeeded'   => 'green',
                'deleted', 'force_deleted', 'login_failed' => 'red',
                'locked_out', 'password_changed'           => 'amber',
                default                                    => 'blue',
            },
        );
    }

    protected function instanceId(): ?int
    {
        if (! function_exists('instance_context')) {
            return null;
        }

        return instance_context()?->instance->getKey();
    }
}
