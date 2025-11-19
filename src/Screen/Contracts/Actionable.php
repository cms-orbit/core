<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Contracts;

use CmsOrbit\Core\Screen\Repository;

interface Actionable
{
    /**
     * @return mixed
     */
    public function build(Repository $repository);
}
