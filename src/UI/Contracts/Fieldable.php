<?php

declare(strict_types=1);

namespace CmsOrbit\Core\UI\Contracts;

interface Fieldable
{
    /**
     * Render the field.
     *
     * @return mixed
     */
    public function render();
}
