<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;
use Illuminate\Support\Arr;

/**
 * Class Accordion.
 */
abstract class Accordion extends Layout
{
    /**
     * @var string
     */
    protected $template = 'orbit::layouts.accordion';

    /**
     * @var array
     */
    protected $variables = [
        'stayOpen' => false,
        'open' => [],
    ];

    /**
     * Layout constructor.
     *
     * @param  Layout[]  $layouts
     */
    public function __construct(array $layouts = [])
    {
        $this->layouts = $layouts;
        $this->variables['open'] = [array_key_first($this->layouts)];
    }

    /**
     * @return mixed
     */
    public function build(Repository $repository)
    {
        return $this->buildAsDeep($repository);
    }

    /**
     * Make accordion items stay open when another item is opened.
     *
     *
     * @return $this
     */
    public function stayOpen(bool $stayOpen = true): self
    {
        $this->variables['stayOpen'] = $stayOpen;

        return $this;
    }

    /**
     * Set active accordion(s).
     *
     *
     * @return $this
     */
    public function open(string|array $activeAccordion): self
    {
        $this->variables['open'] = Arr::wrap($activeAccordion);

        return $this;
    }
}
