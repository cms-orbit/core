<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities\Screens;

use CmsOrbit\Core\Crud\Layouts\ResourceFields;
use CmsOrbit\Core\Crud\Screens\CreateScreen;
use CmsOrbit\Core\Entities\UserEntity;
use CmsOrbit\Core\Support\Facades\Layout;
use LogicException;

class UserCreateScreen extends CreateScreen
{
    public function layout(): array
    {
        $entity = $this->userEntity();

        return [
            Layout::block([
                new ResourceFields($entity->basicFields()),
            ])
                ->title(__('Basic user information'))
                ->description(__('Manage the user profile, password, avatar, and assigned roles.')),

            Layout::block([
                new ResourceFields($entity->permissionOverrideFields()),
            ])
                ->title(__('Permission overrides'))
                ->description(__('Allow or deny individual permissions separately from the assigned roles.')),
        ];
    }

    protected function userEntity(): UserEntity
    {
        $entity = $this->entity();

        if (! $entity instanceof UserEntity) {
            throw new LogicException('UserCreateScreen expects the UserEntity.');
        }

        return $entity;
    }
}
