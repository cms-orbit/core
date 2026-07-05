<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Filters\Filter;
use CmsOrbit\Core\Filters\FilterQuery;
use CmsOrbit\Core\Foundation\Entity\EntityRegistry;
use CmsOrbit\Core\Screen\Builder;
use CmsOrbit\Core\Screen\Contracts\Fieldable;
use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;
use CmsOrbit\Core\Screen\TD;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Pagination\UrlWindow;
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

    protected bool $withToolbar = false;

    /**
     * @var array<int, int>|null
     */
    protected ?array $perPageOptions = null;

    protected bool $withSearch = false;

    protected string $paginationStyle = 'full';

    /**
     * Entity-level filter classes rendered in the table toolbar.
     *
     * @var array<int, mixed>
     */
    protected array $toolbarFilters = [];

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
     * Enable the integrated Filament-style table toolbar.
     */
    public function toolbar(bool $enabled = true): static
    {
        $this->withToolbar = $enabled;

        return $this;
    }

    /**
     * Enable the global table search field (`filter[search]`).
     */
    public function searchable(bool $enabled = true): static
    {
        $this->withSearch = $enabled;

        return $this;
    }

    /**
     * @param array<int, mixed> $filters
     */
    public function toolbarFilters(array $filters): static
    {
        $this->toolbarFilters = $filters;

        return $this;
    }

    /**
     * @param array<int, int> $options
     */
    public function perPageOptions(array $options): static
    {
        $this->perPageOptions = $options;

        return $this;
    }

    /**
     * Pagination navigation style: `full` (page numbers) or `simple` (prev/next only).
     */
    public function paginationStyle(string $style): static
    {
        $this->paginationStyle = in_array($style, ['full', 'simple'], true) ? $style : 'full';

        return $this;
    }

    /**
     * @return array<int, int>
     */
    protected function resolvePerPageOptions(): array
    {
        if ($this->perPageOptions !== null) {
            return $this->perPageOptions;
        }

        $entityKey = request()->route('entity');

        if (! is_string($entityKey)) {
            return [10, 25, 50, 100];
        }

        try {
            return app(EntityRegistry::class)
                ->findOrFail($entityKey)
                ->perPageOptions();
        } catch (\Throwable) {
            return [10, 25, 50, 100];
        }
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

        $allFilters = $this->serializeFilterFields($repository, $columns);
        [$inlineFilterFields, $popoverFilterFields] = $this->partitionFilterFields($allFilters);

        return [
            'title'   => $this->title,
            'target'  => $this->target,
            'columns' => $columns
                ->map(static fn (TD $column) => $column->toArray())
                ->all(),
            'rows'               => $this->serializeRows($repository, $columns),
            'pagination'         => $this->serializePagination($repository),
            'toolbarFilters'     => $this->serializeToolbarFilters(),
            'filterFields'       => $popoverFilterFields,
            'inlineFilterFields' => $inlineFilterFields,
            'search'             => [
                'enabled' => $this->withSearch,
                'value'   => request()->input('filter.search'),
            ],
            'withSearch'      => $this->withSearch,
            'searchColumn'    => $this->withSearch ? 'search' : null,
            'striped'         => $this->striped(),
            'compact'         => $this->compact(),
            'bordered'        => $this->bordered(),
            'hoverable'       => $this->hoverable(),
            'onEachSide'      => $this->onEachSide(),
            'iconNotFound'    => $this->iconNotFound(),
            'textNotFound'    => $this->textNotFound(),
            'subNotFound'     => $this->subNotFound(),
            'withToolbar'     => $this->withToolbar,
            'perPageOptions'  => $this->resolvePerPageOptions(),
            'paginationStyle' => $this->paginationStyle,
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
     * @return array<int, array<string, mixed>>
     */
    protected function serializeToolbarFilters(): array
    {
        if ($this->toolbarFilters === []) {
            return [];
        }

        $fields = collect($this->toolbarFilters)
            ->map(static fn ($filter) => is_string($filter) ? resolve($filter) : $filter)
            ->filter(static fn ($filter) => $filter instanceof Filter && $filter->isDisplay())
            ->flatMap(static fn (Filter $filter) => collect($filter->display())
                ->map(static fn (Fieldable $field) => $field->set('form', 'filters')))
            ->all();

        return (new Builder($fields, new Repository(request()->all())))->generateArray();
    }

    /**
     * @param Collection<int, TD> $columns
     *
     * @return array<int, array<string, mixed>>
     */
    protected function serializeFilterFields(Repository $repository, Collection $columns): array
    {
        $toolbar = $this->serializeToolbarFilters();

        $columnFilters = $columns
            ->filter(static fn (TD $column) => ! $column->toArray()['filterTabs'])
            ->map(static fn (TD $column) => $column->toArray()['filter'] ?? null)
            ->filter()
            ->values()
            ->all();

        return array_values(array_merge($toolbar, $columnFilters));
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    protected function partitionFilterFields(array $fields): array
    {
        $inline = [];
        $popover = [];

        foreach ($fields as $field) {
            if (($field['attributes']['inline'] ?? false) === true) {
                $inline[] = $field;
            } else {
                $popover[] = $field;
            }
        }

        return [$inline, $popover];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function serializePagination(Repository $repository): ?array
    {
        $content = $repository->getContent($this->target);

        if (! $content instanceof LengthAwarePaginator) {
            return null;
        }

        $paginator = $content
            ->appends(FilterQuery::normalizeQueryParams(request()->query()))
            ->onEachSide($this->onEachSide());

        return [
            'current_page'   => $paginator->currentPage(),
            'last_page'      => $paginator->lastPage(),
            'total'          => $paginator->total(),
            'from'           => $paginator->firstItem(),
            'to'             => $paginator->lastItem(),
            'per_page'       => $paginator->perPage(),
            'prev_page_url'  => $paginator->previousPageUrl(),
            'next_page_url'  => $paginator->nextPageUrl(),
            'first_page_url' => $paginator->url(1),
            'last_page_url'  => $paginator->lastPage() > 0 ? $paginator->url($paginator->lastPage()) : null,
            'links'          => $this->serializePaginationLinks($paginator),
            'style'          => $this->paginationStyle,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function serializePaginationLinks(LengthAwarePaginator $paginator): array
    {
        $window = UrlWindow::make($paginator);

        $elements = array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]);

        $links = [];

        foreach ($elements as $element) {
            if (is_string($element)) {
                $links[] = ['type' => 'ellipsis'];

                continue;
            }

            foreach ($element as $page => $url) {
                $pageNumber = (int) $page;

                $links[] = [
                    'type'   => 'page',
                    'page'   => $pageNumber,
                    'url'    => $url,
                    'active' => $paginator->currentPage() === $pageNumber,
                ];
            }
        }

        return $links;
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
