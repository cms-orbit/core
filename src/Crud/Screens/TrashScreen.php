<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Crud\Screens;

use CmsOrbit\Core\Crud\CrudScreen;
use CmsOrbit\Core\Crud\Requests\EntityRequest;
use CmsOrbit\Core\Screen\Action;
use CmsOrbit\Core\Screen\Actions\Link;
use CmsOrbit\Core\Screen\TD;
use CmsOrbit\Core\Support\Facades\Layout;
use Illuminate\Database\Eloquent\Model;

/**
 * Soft-deleted record listing ("trash"). Only registered for entities whose
 * model uses SoftDeletes and whose {@see Entity::crud()} includes "trash".
 * Restore / force-delete happen on the per-row edit screen, which already
 * surfaces those buttons for trashed models.
 */
class TrashScreen extends CrudScreen
{
    /**
     * @return array<string, mixed>
     */
    public function query(EntityRequest $request): array
    {
        $entity = $this->entity();

        return [
            'model' => $entity->query()
                ->onlyTrashed()
                ->with($entity->with())
                ->paginate($entity->perPage()),
        ];
    }

    public function name(): ?string
    {
        return __('Trash').' · '.$this->entity()->label();
    }

    public function description(): ?string
    {
        return __('Soft-deleted :resource you can restore or permanently remove.', [
            'resource' => $this->entity()->label(),
        ]);
    }

    /**
     * Gate the trash listing behind the restore ability.
     *
     * @return iterable<int, string>|null
     */
    public function permission(): ?iterable
    {
        return [$this->entity()->abilityPermission('restore')];
    }

    /**
     * @return Action[]
     */
    public function commandBar(): array
    {
        $entity = $this->entity();

        return [
            Link::make(__('Back to :resource', ['resource' => $entity->label()]))
                ->route('orbit.entities.'.$entity::uriKey().'.index')
                ->icon('bs.arrow-left'),
        ];
    }

    /**
     * @return \CmsOrbit\Core\Screen\Layout[]
     */
    public function layout(): array
    {
        $entity = $this->entity();
        $grid = collect($entity->columns());

        $grid->push(
            TD::make(__('Actions'))
                ->alignRight()
                ->cantHide()
                ->render(fn (Model $model) => Link::make(__('Manage'))
                    ->icon('bs.arrow-clockwise')
                    ->canSee($this->can('restore') || $this->can('forceDelete'))
                    ->route('orbit.entities.'.$entity::uriKey().'.edit', ['id' => $model->getKey()]))
        );

        return [
            Layout::table('model', $grid->toArray()),
        ];
    }
}
