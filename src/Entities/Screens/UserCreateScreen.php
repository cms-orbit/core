<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities\Screens;

use CmsOrbit\Core\Crud\Screens\CreateScreen;
use CmsOrbit\Core\Entities\UserEntity;
use LogicException;

class UserCreateScreen extends CreateScreen
{
    public function layout(): array
    {
        return $this->userEntity()->formLayouts();
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
