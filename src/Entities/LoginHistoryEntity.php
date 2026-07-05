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

class LoginHistoryEntity extends Entity
{
    use RendersAuditCells;

    public static function uriKey(): string
    {
        return 'login-history';
    }

    public function model(): string
    {
        return OrbitActivity::class;
    }

    public function query(): Builder
    {
        return parent::query()
            ->forInstance($this->instanceId())
            ->loginHistory()
            ->latest('created_at');
    }

    public function label(): string
    {
        return __('Login History');
    }

    public function singularLabel(): string
    {
        return __('Login Event');
    }

    public function icon(): string
    {
        return 'bs.person-badge';
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
        return 1300;
    }

    public function menuParent(): ?string
    {
        return 'access-control-identity';
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
            TD::make('event', __('Event'))
                ->sort()
                ->filter(TD::FILTER_SELECT, OrbitActivity::loginHistoryEventOptions())
                ->filterAsTabs()
                ->render(fn (Model $model) => $this->renderEvent($model)),
            TD::make('subject_id', __('User'))
                ->filter(TD::FILTER_SELECT, $this->userOptions())
                ->filterInline()
                ->render(fn (Model $model) => e((string) ($model->getAttribute('subject_label') ?? $model->getAttribute('auth_identifier') ?? '—'))),
            TD::make('causer_label', __('Actor'))
                ->defaultHidden()
                ->render(fn (Model $model) => e((string) ($model->getAttribute('causer_label') ?? '—'))),
            TD::make('auth_identifier', __('Identifier'))
                ->render(fn (Model $model) => e((string) ($model->getAttribute('auth_identifier') ?? '—'))),
            TD::make('ip_address', __('IP'))
                ->render(fn (Model $model) => $this->renderCodeValue($model->getAttribute('ip_address'))),
            TD::make('browser_family', __('Browser'))
                ->filter(TD::FILTER_SELECT, $this->browserOptions())
                ->render(fn (Model $model) => e((string) ($model->getAttribute('browser_family') ?? '—'))),
        ];
    }

    public function legend(): array
    {
        return [
            Sight::make('created_at', __('Occurred'))
                ->render(fn (Model $model) => $this->renderTimestamp($model->getAttribute('created_at'))),
            Sight::make('event', __('Event'))
                ->render(fn (Model $model) => $this->renderEvent($model)),
            Sight::make('description', __('Description')),
            Sight::make('subject_label', __('User')),
            Sight::make('causer_label', __('Actor')),
            Sight::make('auth_identifier', __('Identifier')),
            Sight::make('ip_address', __('IP'))
                ->render(fn (Model $model) => $this->renderCodeValue($model->getAttribute('ip_address'))),
            Sight::make('browser_family', __('Browser')),
            Sight::make('device_type', __('Device')),
            Sight::make('properties', __('Details'))
                ->render(fn (Model $model) => $this->renderProperties($model->getAttribute('properties'))),
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

    /**
     * @return array<string, string>
     */
    protected function browserOptions(): array
    {
        return OrbitActivity::query()
            ->forInstance($this->instanceId())
            ->loginHistory()
            ->whereNotNull('browser_family')
            ->distinct()
            ->orderBy('browser_family')
            ->pluck('browser_family', 'browser_family')
            ->all();
    }

    protected function renderEvent(Model $model): string
    {
        $event = (string) $model->getAttribute('event');

        return $this->renderBadge(
            OrbitActivity::loginHistoryEventOptions()[$event] ?? $event,
            match ($event) {
                'login_succeeded', 'logged_out'  => 'green',
                'login_failed'                   => 'red',
                'locked_out', 'password_changed' => 'amber',
                default                          => 'blue',
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
