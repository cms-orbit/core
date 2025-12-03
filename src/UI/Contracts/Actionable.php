<?php

declare(strict_types=1);

namespace CmsOrbit\Core\UI\Contracts;

use CmsOrbit\Core\UI\Repository;

interface Actionable
{
    /**
     * @return mixed
     */
    public function build(Repository $repository);
}
