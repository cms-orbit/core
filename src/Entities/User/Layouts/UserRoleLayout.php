<?php

namespace CmsOrbit\Core\Entities\User\Layouts;

use CmsOrbit\Core\Auth\Models\Role;
use CmsOrbit\Core\UI\Field;
use CmsOrbit\Core\UI\Fields\Select;
use CmsOrbit\Core\UI\Layouts\Rows;

class UserRoleLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Select::make('user.roles.')
                ->fromModel(Role::class, 'name')
                ->multiple()
                ->title(__('Name role'))
                ->help(__('Specify which groups this account should belong to')),
        ];
    }
}
