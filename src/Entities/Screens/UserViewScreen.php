<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities\Screens;

use CmsOrbit\Core\Crud\Screens\ViewScreen;
use CmsOrbit\Core\Entities\UserEntity;
use LogicException;

class UserViewScreen extends ViewScreen
{
    public function layout(): array
    {
        if ($this->model === null) {
            return [];
        }

        return $this->userEntity()->viewLayouts($this->model);
    }

    protected function userEntity(): UserEntity
    {
        $entity = $this->entity();

        if (! $entity instanceof UserEntity) {
            throw new LogicException('UserViewScreen expects the UserEntity.');
        }

        return $entity;
    }
}
