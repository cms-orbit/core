<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Contracts;

use CmsOrbit\Core\Screen\Field;

interface Groupable extends Fieldable
{
    /**
     * @return Field[]
     */
    public function getGroup(): array;

    public function setGroup(array $group = []): self;
}
