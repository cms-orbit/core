<?php

declare(strict_types=1);

namespace CmsOrbit\Core\UI\Layouts;

use CmsOrbit\Core\UI\Layout;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

/**
 * Vue Component Layout
 * 
 * Orbit 레이아웃에 Vue 컴포넌트를 삽입
 */
class VueComponentLayout extends Layout
{
    /**
     * Vue component name
     *
     * @var string
     */
    protected string $component;

    /**
     * Component props
     *
     * @var array
     */
    protected array $props;

    /**
     * Root element classes
     *
     * @var string
     */
    protected string $rootClasses;

    /**
     * Constructor
     *
     * @param string $component
     * @param array $props
     * @param string $rootClasses
     */
    public function __construct(string $component, array $props = [], string $rootClasses = '')
    {
        $this->component = $component;
        $this->props = $props;
        $this->rootClasses = $rootClasses;
    }

    /**
     * Build the layout
     *
     * @param \CmsOrbit\Core\UI\Repository $repository
     * @return Factory|View
     */
    public function build(\CmsOrbit\Core\UI\Repository $repository): Factory|View
    {
        return view('orbit::layouts.vue-component', [
            'component' => $this->component,
            'props' => $this->props,
            'rootClasses' => $this->rootClasses,
        ]);
    }
}

