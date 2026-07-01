<?php

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;
use CmsOrbit\Core\Screen\Sight;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

abstract class Sortable extends Layout
{
    /**
     * @var string
     */
    protected $template = 'orbit::layouts.sortable';

    /**
     * Used to create the title of a group of form elements.
     *
     * @var string|null
     */
    protected $title;

    /**
     * @var Repository
     */
    protected $query;

    /**
     * Flag indicating whether block headers are hidden or shown.
     */
    protected bool $showBlockHeaders = false;

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
     * @return Factory|View|null
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (! $this->isSee()) {
            return;
        }

        $columns = collect($this->columns())->filter(static fn (Sight $sight) => $sight->isSee());

        $rows = collect()->merge($repository->getContent($this->target));

        return view($this->template, [
            'rows' => $rows,
            'columns' => $columns,
            'slug' => $this->getSlug(),
            'title' => $this->title,
            'showBlockHeaders' => $this->showBlockHeaders,
            'iconNotFound' => $this->iconNotFound(),
            'textNotFound' => $this->textNotFound(),
            'subNotFound' => $this->subNotFound(),
            'successSortMessage' => $this->successSortMessage(),
            'failureSortMessage' => $this->failureSortMessage(),
        ]);
    }

    /**
     * Serialize the sortable list (columns + rows) to the React contract.
     *
     * @return array<string, mixed>
     */
    protected function serialize(Repository $repository): array
    {
        $columns = collect($this->columns())
            ->filter(static fn (Sight $sight) => $sight->isSee())
            ->map(static fn (Sight $sight) => $sight->toArray($repository))
            ->values()
            ->all();

        return [
            'title' => $this->title,
            'target' => $this->target,
            'columns' => $columns,
            'rows' => collect($repository->getContent($this->target))->values()->all(),
            'showBlockHeaders' => $this->showBlockHeaders,
            'textNotFound' => $this->textNotFound(),
            'subNotFound' => $this->subNotFound(),
        ];
    }

    /**
     * @return array
     */
    abstract protected function columns(): iterable;

    /**
     * @return Rows
     */
    public function title(?string $title = null): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Show or hide block headers.
     *
     * @param  bool  $showHeaders  Whether to show block headers or not. Default is false.
     * @return $this
     */
    public function showBlockHeaders(bool $showHeaders = true): self
    {
        $this->showBlockHeaders = $showHeaders;

        return $this;
    }

    protected function iconNotFound(): string
    {
        return 'bs.journal-x';
    }

    protected function textNotFound(): string
    {
        return __('There are no objects currently displayed');
    }

    protected function subNotFound(): string
    {
        return __('Import or create objects, or check back later for updates');
    }

    /**
     * Return a success message for sorting operation.
     */
    public function successSortMessage(): string
    {
        return __('Sorting was successful.');
    }

    /**
     * Return a failure message for sorting operation.
     */
    public function failureSortMessage(): string
    {
        return __('Sorting failed. Please try again.');
    }
}
