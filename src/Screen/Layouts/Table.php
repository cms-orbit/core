<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;
use CmsOrbit\Core\Screen\TD;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Class Table.
 */
abstract class Table extends Layout
{
    /**
     * @var string
     */
    protected $template = 'orbit::layouts.table';

    /**
     * @var Repository
     */
    protected $query;

    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target;

    /**
     * Table title.
     *
     * The string to be displayed on top of the table.
     *
     * @var string
     */
    protected $title;

    /**
     * Build the table view with columns, rows, and other settings.
     *
     * @return Factory|View|void
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (! $this->isSee()) {
            return;
        }

        $columns = collect($this->columns())->filter(static fn (TD $column) => $column->isSee());

        $total = collect($this->total())->filter(static fn (TD $column) => $column->isSee());

        $content = $repository->getContent($this->target);

        $rows = is_a($content, Paginator::class) || is_a($content, CursorPaginator::class) ? $content : collect()->merge($content);

        return view($this->template, [
            'repository'   => $repository,
            'rows'         => $rows,
            'columns'      => $columns,
            'total'        => $total,
            'iconNotFound' => $this->iconNotFound(),
            'textNotFound' => $this->textNotFound(),
            'subNotFound'  => $this->subNotFound(),
            'striped'      => $this->striped(),
            'compact'      => $this->compact(),
            'bordered'     => $this->bordered(),
            'hoverable'    => $this->hoverable(),
            'slug'         => $this->getSlug(),
            'onEachSide'   => $this->onEachSide(),
            'showHeader'   => $this->hasHeader($columns, $rows),
            'title'        => $this->title,
        ]);
    }

    /**
     * Set the table title.
     */
    public function title(?string $title = null): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Icon displayed when no data is found.
     */
    protected function iconNotFound(): string
    {
        return 'bs.journal-x';
    }

    /**
     * Text displayed when no data is found.
     */
    protected function textNotFound(): string
    {
        if (count(request()->query()) !== 0) {
            return __('No results found for your current filters');
        }

        return __('There are no objects currently displayed');
    }

    /**
     * Subtext displayed when no data is found.
     */
    protected function subNotFound(): string
    {
        if (count(request()->query()) !== 0) {
            return __('Try adjusting your filter settings or removing it altogether to see more data');
        }

        return __('Import or create objects, or check back later for updates');
    }

    /**
     * Usage for zebra-striping to any table row.
     */
    protected function striped(): bool
    {
        return false;
    }

    /**
     * Usage for compact display of table rows.
     */
    protected function compact(): bool
    {
        return false;
    }

    /**
     * Usage for borders on all sides of the table and cells.
     */
    protected function bordered(): bool
    {
        return false;
    }

    /**
     * Enable a hover state on table rows.
     */
    protected function hoverable(): bool
    {
        return false;
    }

    /**
     * The number of links to display on each side of the current page link.
     */
    protected function onEachSide(): int
    {
        return 3;
    }

    /**
     * Determine if table header should be displayed.
     */
    protected function hasHeader(Collection $columns, Collection|Paginator|CursorPaginator $row): bool
    {
        if ($columns->count() < 2) {
            return false;
        }

        return ! empty(request()->query()) || $row->isNotEmpty();
    }

    /**
     * @return array<string, mixed>
     */
    protected function serialize(Repository $repository): array
    {
        $columns = collect($this->columns())
            ->filter(static fn (TD $column) => $column->isSee())
            ->values();

        return [
            'title'   => $this->title,
            'target'  => $this->target,
            'columns' => $columns
                ->map(static fn (TD $column) => $column->toArray())
                ->all(),
            'rows'         => $this->serializeRows($repository, $columns),
            'striped'      => $this->striped(),
            'compact'      => $this->compact(),
            'bordered'     => $this->bordered(),
            'hoverable'    => $this->hoverable(),
            'onEachSide'   => $this->onEachSide(),
            'iconNotFound' => $this->iconNotFound(),
            'textNotFound' => $this->textNotFound(),
            'subNotFound'  => $this->subNotFound(),
        ];
    }

    /**
     * @param Collection<int, TD> $columns
     *
     * @return array<int, array<string, mixed>>
     */
    protected function serializeRows(Repository $repository, Collection $columns): array
    {
        $content = $repository->getContent($this->target);
        $rows = $content instanceof Paginator || $content instanceof CursorPaginator
            ? collect($content->items())
            : collect()->merge($content);

        return $rows
            ->map(function ($row) use ($columns) {
                $data = $row instanceof Arrayable ? $row->toArray() : (array) $row;
                $cells = $columns
                    ->mapWithKeys(function (TD $column) use ($row) {
                        $cell = $column->toArray($row);

                        return [$cell['slug'] => [
                            'rendered' => $cell['rendered'] ?? null,
                            'field'    => $cell['field'] ?? null,
                            'actions'  => $cell['actions'] ?? null,
                        ]];
                    })
                    ->filter(fn (array $cell) => collect($cell)->contains(fn ($value) => $value !== null && $value !== []))
                    ->all();

                return array_merge($data, ['_cells' => $cells]);
            })
            ->values()
            ->all();
    }

    /**
     * Define table columns.
     *
     * @return iterable|TD[]
     */
    abstract protected function columns(): iterable;

    /**
     * Define total row configuration.
     */
    protected function total(): array
    {
        return [];
    }
}
