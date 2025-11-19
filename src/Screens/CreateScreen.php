<?php

namespace CmsOrbit\Core\Screens;

use Illuminate\Http\RedirectResponse;
use CmsOrbit\Core\Crud\CrudScreen;
use CmsOrbit\Core\Crud\Layouts\ResourceFields;
use CmsOrbit\Core\Crud\Requests\CreateRequest;
use CmsOrbit\Core\Screen\Action;
use CmsOrbit\Core\Screen\Actions\Button;
use CmsOrbit\Core\Support\Facades\Toast;

class CreateScreen extends CrudScreen
{
    /**
     * Query data.
     *
     * @param CreateRequest $request
     *
     * @return array
     */
    public function query(CreateRequest $request): array
    {
        return [
            ResourceFields::PREFIX => $request->model(),
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
            Button::make($this->resource::createButtonLabel())
                ->method('save')
                ->icon('bs.check-circle'),
        ];
    }

    /**
     * Views.
     *
     * @return \CmsOrbit\Core\Screen\Layout[]
     */
    public function layout(): array
    {
        return [
            new ResourceFields($this->resource->fields()),
        ];
    }

    /**
     * @param CreateRequest $request
     *
     * @return RedirectResponse
     */
    public function save(CreateRequest $request)
    {
        $model = $request->model();

        $request->resource()->save($request, $model);

        Toast::info($this->resource::createToastMessage());

        return redirect()->route('settings.resource.list', $request->resource);
    }
}
