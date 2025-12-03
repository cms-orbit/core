<?php

namespace CmsOrbit\Core\Entities\User\Layouts;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Password;
use Orchid\Screen\Layouts\Rows;

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
