<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities\Screens;

use CmsOrbit\Core\Crud\Screens\EditScreen;
use CmsOrbit\Core\Entities\UserEntity;
use LogicException;

class UserEditScreen extends EditScreen
{
    public function layout(): array
    {
        return $this->userEntity()->formLayouts($this->model);
    }

    protected function userEntity(): UserEntity
    {
        $entity = $this->entity();

        if (! $entity instanceof UserEntity) {
            throw new LogicException('UserEditScreen expects the UserEntity.');
        }

        return $entity;
    }
}
