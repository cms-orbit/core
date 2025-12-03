<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Layouts;

use Illuminate\Contracts\View\Factory;
use CmsOrbit\Core\UI\Layout;
use CmsOrbit\Core\UI\Repository;
use CmsOrbit\Core\UI\Sight;

/**
 * Class Legend.
 */
abstract class Legend extends Layout
{
    /**
     * @var string
     */
    protected $template = 'settings::layouts.legend';

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
     * @return Factory|\Illuminate\View\View|null
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
            'columns'    => $columns,
            'slug'       => $this->getSlug(),
            'title'      => $this->title,
        ]);
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
