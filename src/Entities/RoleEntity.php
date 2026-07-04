<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities;

use CmsOrbit\Core\Entities\Concerns\LogsCrudActivity;
use CmsOrbit\Core\Entities\Concerns\RendersAccessBadges;
use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Foundation\Models\Role;
use CmsOrbit\Core\Screen\Field;
use CmsOrbit\Core\Screen\Fields\Input;
use CmsOrbit\Core\Screen\Fields\PermissionMatrix;
use CmsOrbit\Core\Screen\Fields\ReactField;
use CmsOrbit\Core\Screen\Sight;
use CmsOrbit\Core\Screen\TD;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\CarbonInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Built-in admin entity for roles. A role is little more than a named bundle of
 * permission slugs; the selected slugs are stored as the Orbit-style
 * `{slug: true}` JSON map via {@see onSave()}.
 */
class RoleEntity extends Entity
{
    use LogsCrudActivity;
    use RendersAccessBadges;

    public function model(): string
    {
        return Orbit::model(Role::class);
    }

    public function icon(): string
    {
        return 'bs.shield-lock';
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
        return 1100;
    }

    public function menuParent(): ?string
    {
        return 'access-control-identity';
    }

    /**
     * @return Field[]
     */
    public function fields(): array
    {
        $role = $this->currentRole();
        $isSuperAdmin = $role?->isSuperAdmin() ?? false;

        $slug = Input::make('slug')
            ->title(__('Slug'))
            ->required(! $isSuperAdmin)
            ->placeholder(__('editor'));

        $permissions = PermissionMatrix::make('permissions')
            ->title(__('Permissions'))
            ->permissions(Orbit::getPermission())
            ->help($isSuperAdmin
                ? __('The default super-admin role always includes every permission.')
                : __('Every user assigned this role inherits these permissions.'));

        if ($isSuperAdmin) {
            $slug->disabled()->readonly()
                ->help(__('The default super-admin slug cannot be changed.'));

            $permissions->disabled();
        }

        return [
            Input::make('name')->title(__('Name'))->required()->placeholder(__('Editor')),
            $slug,
            $permissions,
        ];
    }

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', __('ID'))
                ->sort()
                ->width(120)
                ->render(fn (Model $model) => $this->roleIdField($model)),
            TD::make('name', __('Name'))->sort(),
            TD::make('slug', __('Slug'))->sort(),
            TD::make('created_at', __('Created'))
                ->sort()
                ->defaultHidden()
                ->render(fn (Model $model) => $this->renderTimestamp($model->getAttribute('created_at'))),
        ];
    }

    /**
     * @return Sight[]
     */
    public function legend(): array
    {
        return [
            Sight::make('id', __('ID')),
            Sight::make('name', __('Name')),
            Sight::make('slug', __('Slug')),
            Sight::make('permissions', __('Permissions'))
                ->render(fn (Model $model) => $this->permissionSummary(
                    collect($model->getAttribute('permissions') ?? [])->filter()->all(),
                    __('No permissions')
                )),
            Sight::make('created_at', __('Created'))
                ->render(fn (Model $model) => $this->renderTimestamp($model->getAttribute('created_at'))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(Model $model): array
    {
        $isSuperAdmin = $model instanceof Role && $model->isSuperAdmin();

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique($model->getTable(), 'name')->ignore($model->getKey()),
            ],
            'slug' => [
                Rule::requiredIf(! $isSuperAdmin),
                'nullable',
                'string',
                'max:255',
                'lowercase',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique($model->getTable(), 'slug')->ignore($model->getKey()),
            ],
            'permissions' => ['nullable', 'array'],
        ];
    }

    public function onSave(Request $request, Model $model): void
    {
        $this->persistWithActivity($request, $model, function () use ($request, $model): void {
            $model->forceFill([
                'name' => $request->input('name'),
                'slug' => $request->filled('slug')
                    ? Str::lower((string) $request->input('slug'))
                    : $model->getAttribute('slug'),
                'permissions' => $this->permissionMap($request, $model),
            ])->save();
        });
    }

    public function canDelete(Model $model): bool
    {
        return ! method_exists($model, 'isProtected') || ! $model->isProtected();
    }

    public function deleteBlockedMessage(Model $model): string
    {
        return __('The default super-admin role cannot be deleted.');
    }

    public function onDelete(Model $model): void
    {
        $this->deleteWithActivity($model, fn () => $model->delete());
    }

    /**
     * @return array<string, bool>
     */
    protected function permissionMap(Request $request, Model $model): array
    {
        if ($model instanceof Role && $model->isSuperAdmin()) {
            return Orbit::getAllowAllPermission()->toArray();
        }

        if (! $request->exists('permissions')) {
            return collect($model->getAttribute('permissions') ?? [])
                ->filter()
                ->mapWithKeys(fn ($enabled, $slug) => [(string) $slug => (bool) $enabled])
                ->all();
        }

        return collect($request->input('permissions', []))
            ->filter()
            ->mapWithKeys(fn ($slug) => [(string) $slug => true])
            ->all();
    }

    protected function currentRole(): ?Role
    {
        $id = request()->route('id');

        if (! is_string($id) || blank($id)) {
            return null;
        }

        $roleModelClass = Orbit::model(Role::class);
        $role = $roleModelClass::query()->find($id);

        return $role instanceof Role ? $role : null;
    }

    protected function roleIdField(Model $model): ReactField
    {
        $id = (string) $model->getKey();

        return ReactField::make('id')
            ->component('RoleIdCell')
            ->value($id)
            ->props([
                'fullId'      => $id,
                'shortId'     => Str::limit($id, 5, '...'),
                'copyLabel'   => __('Copy ID'),
                'copiedLabel' => __('Copied'),
            ]);
    }

    /**
     * @param array<string, bool> $permissions
     */
    protected function permissionSummary(array $permissions, string $empty): string
    {
        if ($permissions === []) {
            return '<span class="text-sm text-gray-400">'.e($empty).'</span>';
        }

        $selected = array_keys($permissions);
        $grouped = Orbit::getPermission()
            ->map(function ($items, $group) use ($selected): array {
                $matches = collect($items)
                    ->filter(fn (array $item) => in_array((string) $item['slug'], $selected, true))
                    ->map(fn (array $item) => [
                        'slug'  => (string) $item['slug'],
                        'label' => (string) __((string) ($item['description'] ?? $item['slug'])),
                    ])
                    ->values()
                    ->all();

                return [
                    'group' => (string) __((string) $group),
                    'items' => $matches,
                ];
            })
            ->filter(fn (array $group) => $group['items'] !== [])
            ->values();

        $recognizedSlugs = $grouped
            ->flatMap(fn (array $group) => collect($group['items'])->pluck('slug'))
            ->values()
            ->all();

        $unknown = collect($selected)
            ->reject(fn (string $slug) => in_array($slug, $recognizedSlugs, true))
            ->values()
            ->all();

        if ($unknown !== []) {
            $grouped->push([
                'group' => (string) __('Other'),
                'items' => $unknown,
            ]);
        }

        return $grouped
            ->map(function (array $group): string {
                return sprintf(
                    '<section class="rounded-lg border border-gray-200 p-3 dark:border-gray-700"><header class="mb-2 flex items-center justify-between gap-2"><h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500">%s</h4><span class="text-xs text-gray-400">%d</span></header><div class="flex flex-wrap gap-1">%s</div></section>',
                    e($group['group']),
                    count($group['items']),
                    collect($group['items'])
                        ->map(fn (array|string $item): string => '<span class="inline-flex items-center rounded-full bg-orbit-primary/10 px-2 py-0.5 text-xs font-medium text-orbit-primary">'.e(is_array($item) ? $item['label'] : $item).'</span>')
                        ->implode('')
                );
            })
            ->pipe(fn ($sections) => '<div class="grid max-h-80 grid-cols-1 gap-3 overflow-y-auto pr-1 md:grid-cols-2">'.$sections->implode('').'</div>');
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
