<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Crud\Screens;

use CmsOrbit\Core\Crud\CrudScreen;
use CmsOrbit\Core\Crud\Layouts\ResourceFields;
use CmsOrbit\Core\Crud\Requests\UpdateRequest;
use CmsOrbit\Core\Screen\Action;
use CmsOrbit\Core\Screen\Actions\Button;
use CmsOrbit\Core\Screen\Layout;
use Illuminate\Database\Eloquent\Model;

class EditScreen extends CrudScreen
{
    protected ?Model $model = null;

    /**
     * @return array<string, mixed>
     */
    public function query(UpdateRequest $request): array
    {
        $this->model = $request->findModelOrFail();

        return [
            ResourceFields::PREFIX => $this->model,
        ];
    }

    public function name(): ?string
    {
        return __('Edit :resource', ['resource' => $this->entity()->singularLabel()]);
    }

    /**
     * @return Action[]
     */
    public function commandBar(): array
    {
        return [
            Button::make(__('Update :resource', ['resource' => $this->entity()->singularLabel()]))
                ->canSee($this->can('update'))
                ->method('update')
                ->icon('bs.check-circle'),

            Button::make(__('Delete'))
                ->novalidate()
                ->confirm(__('Are you sure you want to delete this resource?'))
                ->canSee(
                    ! $this->isSoftDeleted($this->model)
                    && $this->can('delete')
                    && $this->model !== null
                    && $this->entity()->canDelete($this->model)
                )
                ->method('delete')
                ->icon('bs.trash'),

            Button::make(__('Force delete'))
                ->novalidate()
                ->confirm(__('Are you sure you want to permanently delete this resource?'))
                ->canSee($this->isSoftDeleted($this->model) && $this->can('forceDelete'))
                ->method('forceDelete')
                ->icon('bs.trash'),

            Button::make(__('Restore'))
                ->novalidate()
                ->canSee($this->isSoftDeleted($this->model) && $this->can('restore'))
                ->method('restore')
                ->icon('bs.arrow-clockwise'),
        ];
    }

    /**
     * @return Layout[]
     */
    public function layout(): array
    {
        return [
            new ResourceFields($this->entity()->fields()),
        ];
    }
}
