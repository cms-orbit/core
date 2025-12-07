<?php

namespace CmsOrbit\Core\Entities\Role\Layouts;

use CmsOrbit\Core\UI\Field;
use CmsOrbit\Core\UI\Fields\Input;
use CmsOrbit\Core\UI\Layouts\Rows;

class RoleEditLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('role.name')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Name'))
                ->placeholder(__('Name')),

            Input::make('role.slug')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Slug'))
                ->placeholder(__('Slug'))
                ->help(__('A unique identifier for this role')),
        ];
    }
}
