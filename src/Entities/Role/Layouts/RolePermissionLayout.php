<?php

namespace CmsOrbit\Core\Entities\Role\Layouts;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Layouts\Rows;
use Orchid\Support\Facades\Dashboard;

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
