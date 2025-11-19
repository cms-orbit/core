<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Layouts;

use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;

/**
 * Class Blank.
 */
abstract class Blank extends Layout
{
    /**
     * @var string
     */
    protected $template = 'settings::layouts.blank';

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
     * @return \Illuminate\View\View|mixed
     */
    public function build(Repository $repository)
    {
        return $this->buildAsDeep($repository);
    }
}
