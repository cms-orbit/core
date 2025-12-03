<?php

namespace CmsOrbit\Core\Resources\Screens;

use Illuminate\Http\RedirectResponse;
use CmsOrbit\Core\Resources\ResourceScreen;
use CmsOrbit\Core\Resources\Layouts\ResourceFields;
use CmsOrbit\Core\Http\Requests\CreateRequest;
use CmsOrbit\Core\UI\Action;
use CmsOrbit\Core\UI\Actions\Button;
use CmsOrbit\Core\Support\Facades\Toast;

class CreateScreen extends ResourceScreen
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
     * @return \CmsOrbit\Core\UI\Layout[]
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

        return redirect()->route('orbit.resource.list', $request->resource);
    }
}
