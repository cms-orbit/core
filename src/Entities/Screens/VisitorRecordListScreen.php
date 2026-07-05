<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities\Screens;

use CmsOrbit\Core\Analytics\VisitorRecordInsights;
use CmsOrbit\Core\Crud\Requests\IndexRequest;
use CmsOrbit\Core\Crud\Screens\ListScreen;
use CmsOrbit\Core\Screen\Action;
use CmsOrbit\Core\Screen\Actions\Link;
use CmsOrbit\Core\Screen\Fields\CheckBox;
use CmsOrbit\Core\Screen\Fields\Group;
use CmsOrbit\Core\Screen\TD;
use CmsOrbit\Core\Support\Facades\Layout;
use Illuminate\Database\Eloquent\Model;

class VisitorRecordListScreen extends ListScreen
{
    /**
     * @return array<string, mixed>
     */
    public function query(IndexRequest $request): array
    {
        return [
            'summary' => app(VisitorRecordInsights::class)->summary($this->instanceId()),
            'model'   => $request->getModelPaginationList(),
        ];
    }

    /**
     * @return Action[]
     */
    public function commandBar(): array
    {
        return [
            $this->actionsButtons(),
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
                ->render(fn (Model $model) => $this->tableActions($model)->autoWidth())
        );

        return [
            Layout::metrics([
                __('Pageviews (30d)') => 'summary.metrics.pageviews',
                __('Top Page')        => 'summary.metrics.topPage',
                __('Top Referrer')    => 'summary.metrics.topReferrer',
                __('Top Device')      => 'summary.metrics.topDevice',
            ])->title(__('Visitor Performance')),

            Layout::table('model', $grid->toArray())
                ->title(__('Records'))
                ->toolbar()
                ->searchable($entity->searchColumns() !== [])
                ->toolbarFilters($entity->filters()),
        ];
    }

    protected function instanceId(): ?int
    {
        if (! function_exists('instance_context')) {
            return null;
        }

        return instance_context()?->instance->getKey();
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

        return Group::make($actions);
    }
}
