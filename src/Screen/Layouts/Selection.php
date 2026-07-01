<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Filters\Filter;
use CmsOrbit\Core\Screen\Builder;
use CmsOrbit\Core\Screen\Contracts\Fieldable;
use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

/**
 * Class Selection.
 */
abstract class Selection extends Layout
{
    /**
     * Drop-down filters.
     */
    public const TEMPLATE_DROP_DOWN = 'orbit::layouts.selection';

    /**
     * Line filters.
     */
    public const TEMPLATE_LINE = 'orbit::layouts.filter';

    /**
     * @var string
     */
    public $template = self::TEMPLATE_DROP_DOWN;

    /**
     * @return Factory|View|mixed
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
            'chunk' => ceil($filters->count() / 4),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function serialize(Repository $repository): array
    {
        $fields = collect($this->filters())
            ->map(static fn ($filter) => is_string($filter) ? resolve($filter) : $filter)
            ->filter(fn (Filter $filter) => $filter->isDisplay())
            ->flatMap(fn (Filter $filter) => collect($filter->display())
                ->map(fn (Fieldable $field) => $field->set('form', 'filters')))
            ->all();

        return [
            'fields' => (new Builder($fields, new Repository(request()->all())))->generateArray(),
        ];
    }

    /**
     * @return Filter[]
     */
    abstract public function filters(): iterable;
}
