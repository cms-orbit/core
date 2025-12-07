<?php

namespace CmsOrbit\Core\Entities\Role\Layouts;

use CmsOrbit\Core\UI\Field;
use CmsOrbit\Core\UI\Fields\CheckBox;
use CmsOrbit\Core\UI\Layouts\Rows;
use CmsOrbit\Core\Support\Facades\Dashboard;

class RolePermissionLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return Dashboard::getPermission()
            ->transform(function ($group) {
                return CheckBox::make('permissions.' . base64_encode($group->slug))
                    ->placeholder($group->description)
                    ->value($group->active)
                    ->sendTrueOrFalse()
                    ->indeterminate($group->active === null);
            })
            ->flatten()
            ->toArray();
    }
}
