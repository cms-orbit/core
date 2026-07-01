<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Crud\Screens;

use CmsOrbit\Core\Crud\CrudScreen;
use CmsOrbit\Core\Crud\Layouts\ResourceFields;
use CmsOrbit\Core\Crud\Requests\CreateRequest;
use CmsOrbit\Core\Screen\Action;
use CmsOrbit\Core\Screen\Actions\Button;
use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Support\Facades\Toast;
use Illuminate\Http\RedirectResponse;

class CreateScreen extends CrudScreen
{
    /**
     * @return array<string, mixed>
     */
    public function query(CreateRequest $request): array
    {
        return [
            ResourceFields::PREFIX => $request->model(),
        ];
    }

    public function name(): ?string
    {
        return __('Create :resource', ['resource' => $this->entity()->singularLabel()]);
    }

    /**
     * @return Action[]
     */
    public function commandBar(): array
    {
        return [
            Button::make(__('Create :resource', ['resource' => $this->entity()->singularLabel()]))
                ->method('save')
                ->icon('bs.check-circle'),
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

    public function save(CreateRequest $request): RedirectResponse
    {
        $model = $request->model();

        $request->entity()->save($request, $model);

        Toast::info(__('The :resource was created!', ['resource' => $this->entity()->singularLabel()]));

        return redirect()->route('orbit.entities.'.$request->entity()::uriKey().'.index');
    }
}
