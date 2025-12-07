<?php

namespace CmsOrbit\Core\Entities\User\Layouts;

use CmsOrbit\Core\UI\Field;
use CmsOrbit\Core\UI\Fields\Password;
use CmsOrbit\Core\UI\Layouts\Rows;

class UserPasswordLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Password::make('user.password')
                ->title(__('Password'))
                ->placeholder(__('Enter password if you want to change it'))
                ->help(__('Leave blank to keep current password')),
        ];
    }
}
