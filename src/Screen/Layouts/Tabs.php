<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Layouts;

use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;

/**
 * Class Tabs.
 */
abstract class Tabs extends Layout
{
    /**
     * @var string
     */
    public $template = 'settings::layouts.tabs';

    /**
     * @var array
     */
    protected $variables = [
        'activeTab'    => null,
    ];

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

    /**
     * @return $this
     */
    public function activeTab(string $name)
    {
        $this->variables['activeTab'] = $name;

        return $this;
    }
}
