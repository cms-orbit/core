<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;
use Illuminate\View\View;

/**
 * Class Blank.
 */
abstract class Blank extends Layout
{
    /**
     * @var string
     */
    protected $template = 'orbit::layouts.blank';

    /**
     * Layout constructor.
     *
     * @param  Layout[]  $layouts
     */
    public function __construct(array $layouts = [])
    {
        $this->layouts = $layouts;
    }

    /**
     * @return View|mixed
     */
    public function build(Repository $repository)
    {
        return $this->buildAsDeep($repository);
    }
}
