<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Layouts;

use CmsOrbit\Core\UI\Layout;
use CmsOrbit\Core\UI\Repository;

/**
 * Class Columns.
 */
abstract class Columns extends Layout
{
    /**
     * @var string
     */
    protected $template = 'settings::layouts.columns';

    /**
     * Layout constructor.
     *
     * @param Layout[] $layouts
     */
    public function __construct(array $layouts = [])
    {
        $this->layouts = $layouts;
    }

    /**
     * @return mixed
     */
    public function build(Repository $repository)
    {
        return $this->buildAsDeep($repository);
    }
}
