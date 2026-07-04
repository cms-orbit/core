<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Crud\Screens;

use CmsOrbit\Core\Crud\CrudScreen;
use CmsOrbit\Core\Crud\Requests\IndexRequest;
use CmsOrbit\Core\Screen\Action;
use CmsOrbit\Core\Screen\Actions\Link;
use CmsOrbit\Core\Screen\Fields\CheckBox;
use CmsOrbit\Core\Screen\Fields\Group;
use CmsOrbit\Core\Screen\TD;
use CmsOrbit\Core\Support\Facades\Layout;
use Illuminate\Database\Eloquent\Model;

class ListScreen extends CrudScreen
{
    /**
     * @return array<string, mixed>
     */
    public function query(IndexRequest $request): array
    {
        return [
            'model' => $request->getModelPaginationList(),
        ];
    }

    public function description(): ?string
    {
        return null;
    }

    /**
     * @return Action[]
     */
    public function commandBar(): array
    {
        $entity = $this->entity();
        $create = Link::make(__('Create :resource', ['resource' => $entity->singularLabel()]))
            ->canSee($entity->hasCrud('create') && $this->can('create'))
            ->icon('bs.plus-circle');

        if ($entity->hasCrud('create')) {
            $create->route('orbit.entities.'.$entity::uriKey().'.create');
        } else {
            $create->url('#');
        }

        return [
            $this->actionsButtons(),
            $create,
        ];
    }

    /**
     * @return \CmsOrbit\Core\Screen\Layout[]
     */
    public function layout(): array
    {
        $entity = $this->entity();
        $grid = collect($entity->columns());

        $grid->prepend(
            TD::make()
                ->width(50)
                ->cantHide()
                ->canSee($this->availableActions()->isNotEmpty())
                ->render(fn (Model $model) => CheckBox::make('_models[]')
                    ->value($model->getKey())
                    ->checked(false))
        );

        $grid->push(
            TD::make(__('Actions'))
                ->alignRight()
                ->cantHide()
                ->render(fn (Model $model) => $this->tableActions($model)
                    ->autoWidth())
        );

        return [
            Layout::selection($entity->filters()),
            Layout::table('model', $grid->toArray()),
        ];
    }

    private function tableActions(Model $model): Group
    {
        $entity = $this->entity();
        $key = $entity::uriKey();
        $actions = [];

        if ($entity->hasCrud('view')) {
            $actions[] = Link::make(__('View'))
                ->icon('bs.eye')
                ->canSee($this->can('view'))
                ->route('orbit.entities.'.$key.'.view', ['id' => $model->getKey()]);
        }

        if ($entity->hasCrud('edit')) {
            $actions[] = Link::make(__('Edit'))
                ->icon('bs.pencil')
                ->canSee($this->can('update'))
                ->route('orbit.entities.'.$key.'.edit', ['id' => $model->getKey()]);
        }

        return Group::make($actions);
    }
}
