<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Layouts;

use Illuminate\Contracts\View\Factory;
use CmsOrbit\Core\UI\Layout;
use CmsOrbit\Core\UI\Repository;

/**
 * Class Metric.
 */
class Metric extends Layout
{
    /**
     * @var string
     */
    protected $template = 'settings::layouts.metric';

    /**
     * @var string|null
     */
    protected $title;

    /**
     * @var array
     */
    protected $labels = [];

    public function __construct(array $labels)
    {
        $this->labels = $labels;
    }

    /**
     * @return Factory|\Illuminate\View\View
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (! $this->isSee() || empty($this->labels)) {
            return;
        }

        $metrics = collect($this->labels)->map(fn (string $value) => $repository->getContent($value, ''));

        return view($this->template, [
            'title'   => $this->title,
            'metrics' => $metrics,
        ]);
    }

    /**
     * @return $this
     */
    public function title(string $title): Metric
    {
        $this->title = $title;

        return $this;
    }
}
