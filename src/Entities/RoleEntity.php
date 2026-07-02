<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities;

use CmsOrbit\Core\Entities\Concerns\RendersAccessBadges;
use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Foundation\Models\Role;
use CmsOrbit\Core\Screen\Fields\Input;
use CmsOrbit\Core\Screen\Fields\PermissionMatrix;
use CmsOrbit\Core\Screen\Sight;
use CmsOrbit\Core\Screen\TD;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Built-in admin entity for roles. A role is little more than a named bundle of
 * permission slugs; the selected slugs are stored as the Orbit-style
 * `{slug: true}` JSON map via {@see onSave()}.
 */
class RoleEntity extends Entity
{
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

    /**
     * @return \CmsOrbit\Core\Screen\Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('name')->title(__('Name'))->required()->placeholder(__('Editor')),
            PermissionMatrix::make('permissions')
                ->title(__('Permissions'))
                ->permissions(Orbit::getPermission())
                ->help(__('Every user assigned this role inherits these permissions.')),
        ];
    }

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', __('ID'))->sort()->width(80),
            TD::make('name', __('Name'))->sort(),
            TD::make('created_at', __('Created'))->sort()->defaultHidden(),
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
            Sight::make('permissions', __('Permissions'))
                ->render(fn (Model $model) => $this->badgeList(
                    array_keys(collect($model->getAttribute('permissions') ?? [])->filter()->all()),
                    __('No permissions')
                )),
            Sight::make('created_at', __('Created'))
                ->render(fn (Model $model) => optional($model->getAttribute('created_at'))->diffForHumans() ?? '—'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(Model $model): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique($model->getTable(), 'name')->ignore($model->getKey()),
            ],
            'permissions' => ['nullable', 'array'],
        ];
    }

    public function onSave(Request $request, Model $model): void
    {
        $model->forceFill([
            'name' => $request->input('name'),
            'permissions' => $this->permissionMap($request),
        ])->save();
    }

    /**
     * @return array<string, bool>
     */
    protected function permissionMap(Request $request): array
    {
        return collect($request->input('permissions', []))
            ->filter()
            ->mapWithKeys(fn ($slug) => [(string) $slug => true])
            ->all();
    }
}
