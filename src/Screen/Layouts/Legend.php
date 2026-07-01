<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;
use CmsOrbit\Core\Screen\Sight;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;

/**
 * Class Legend.
 */
abstract class Legend extends Layout
{
    /**
     * @var string
     */
    protected $template = 'orbit::layouts.legend';

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

        $repository = $this->target
            ? $repository->getContent($this->target)
            : $repository;

        return view($this->template, [
            'repository' => $repository,
            'columns' => $columns,
            'slug' => $this->getSlug(),
            'title' => $this->title,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function serialize(Repository $repository): array
    {
        // Mirror build(): resolve the target so Sight render closures receive the
        // scoped model (matching the table/list render contract) rather than the
        // whole screen repository.
        $source = $this->target
            ? $repository->getContent($this->target)
            : $repository;

        $columns = collect($this->columns())
            ->filter(static fn (Sight $sight) => $sight->isSee())
            ->map(static fn (Sight $sight) => $sight->toArray($source))
            ->values()
            ->all();

        return [
            'title' => $this->title,
            'target' => $this->target,
            'columns' => $columns,
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
}
