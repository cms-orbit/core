<?php

namespace CmsOrbit\Core\Resources\Screens;

use Illuminate\Database\Eloquent\Model;
use CmsOrbit\Core\Resources\ResourceScreen;
use CmsOrbit\Core\Http\Requests\IndexRequest;
use CmsOrbit\Core\UI\Action;
use CmsOrbit\Core\UI\Actions\Link;
use CmsOrbit\Core\UI\Fields\CheckBox;
use CmsOrbit\Core\UI\Fields\Group;
use CmsOrbit\Core\UI\TD;
use CmsOrbit\Core\Support\Facades\Layout;

class ListScreen extends ResourceScreen
{
    /**
     * Query data.
     *
     * @param IndexRequest $request
     *
     * @return array
     */
    public function query(IndexRequest $request): array
    {
        return [
            'model' => $request->getModelPaginationList(),
        ];
    }

    /**
     * Button commands.
     *
     * @return Action[]
     */
    public function commandBar(): array
    {
        return [
            $this->actionsButtons(),
            Link::make($this->resource::createButtonLabel())
                ->route('orbit.resource.create', $this->resource::uriKey())
                ->canSee($this->can('create'))
                ->icon('bs.plus-circle'),
        ];
    }

    /**
     * Views.
     *
     * @return \CmsOrbit\Core\UI\Layout[]
     */
    public function layout(): array
    {
        $grid = collect($this->resource->columns());

        $grid->prepend(TD::make()
            ->width(50)
            ->cantHide()
            ->canSee($this->availableActions()->isNotEmpty())
            ->render(function (Model $model) {
                return CheckBox::make('_models[]')
                    ->value($model->getKey())
                    ->checked(false);
            }));

        if ($this->resource->canShowTableActions()) {
            $grid->push(TD::make(__('Actions'))
                ->alignRight()
                ->cantHide()
                ->render(function (Model $model) {
                    return $this->getTableActions($model)
                        ->set('align', 'justify-content-end align-items-center')
                        ->autoWidth()
                        ->render();
                }));
        }

        return [
            Layout::selection($this->resource->filters()),
            Layout::table('model', $grid->toArray()),
        ];
    }

    /**
     * @param Model $model
     *
     * @return Group
     */
    private function getTableActions(Model $model): Group
    {
        return Group::make([
            Link::make(__('View'))
                ->icon('bs.eye')
                ->canSee($this->can('view', $model))
                ->route('orbit.resource.view', [
                    $this->resource::uriKey(),
                    $model->getAttribute($model->getKeyName()),
                ]),

            Link::make(__('Edit'))
                ->icon('bs.pencil')
                ->canSee($this->can('update', $model))
                ->route('orbit.resource.edit', [
                    $this->resource::uriKey(),
                    $model->getAttribute($model->getKeyName()),
                ]),
        ]);
    }
}
