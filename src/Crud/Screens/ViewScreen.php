<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Crud\Screens;

use CmsOrbit\Core\Crud\CrudScreen;
use CmsOrbit\Core\Crud\Layouts\ResourceFields;
use CmsOrbit\Core\Crud\Requests\ViewRequest;
use CmsOrbit\Core\Screen\Action;
use CmsOrbit\Core\Screen\Actions\Button;
use CmsOrbit\Core\Screen\Actions\Link;
use CmsOrbit\Core\Support\Facades\Layout;
use Illuminate\Database\Eloquent\Model;

class ViewScreen extends CrudScreen
{
    protected ?Model $model = null;

    /**
     * @return array<string, mixed>
     */
    public function query(ViewRequest $request): array
    {
        $this->model = $request->findModelOrFail();

        return [
            ResourceFields::PREFIX => $this->model,
        ];
    }

    public function name(): ?string
    {
        return $this->entity()->singularLabel();
    }

    /**
     * @return Action[]
     */
    public function commandBar(): array
    {
        $entity = $this->entity();

        return [
            $this->actionsButtons(),

            Link::make(__('Edit'))
                ->icon('bs.pencil')
                ->canSee($this->can('update'))
                ->route('orbit.entities.'.$entity::uriKey().'.edit', ['id' => $this->model?->getKey()]),

            Button::make(__('Delete'))
                ->novalidate()
                ->confirm(__('Are you sure you want to delete this resource?'))
                ->canSee(! $this->isSoftDeleted($this->model) && $this->can('delete'))
                ->method('delete')
                ->icon('bs.trash'),
        ];
    }

    /**
     * @return \CmsOrbit\Core\Screen\Layout[]
     */
    public function layout(): array
    {
        return [
            Layout::legend(ResourceFields::PREFIX, $this->entity()->legend()),
        ];
    }
}
