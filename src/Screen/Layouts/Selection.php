<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Layouts;

use Illuminate\Contracts\View\Factory;
use CmsOrbit\Core\Filters\Filter;
use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;

/**
 * Class Selection.
 */
abstract class Selection extends Layout
{
    /**
     * Drop-down filters.
     */
    public const TEMPLATE_DROP_DOWN = 'settings::layouts.selection';

    /**
     * Line filters.
     */
    public const TEMPLATE_LINE = 'settings::layouts.filter';

    /**
     * @var string
     */
    public $template = self::TEMPLATE_DROP_DOWN;

    /**
     * @return Factory|\Illuminate\View\View|mixed
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (! $this->isSee()) {
            return;
        }

        $filters = collect($this->filters())
            ->map(static fn ($filter) => is_string($filter) ? resolve($filter) : $filter)
            ->filter(fn (Filter $filter) => $filter->isDisplay());

        if ($filters->isEmpty()) {
            return;
        }

        return view($this->template, [
            'filters' => $filters,
            'chunk'   => ceil($filters->count() / 4),
        ]);
    }

    /**
     * @return Filter[]
     */
    abstract public function filters(): iterable;
}
