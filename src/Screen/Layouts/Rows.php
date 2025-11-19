<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Layouts;

use Illuminate\Contracts\View\Factory;
use CmsOrbit\Core\Screen\Builder;
use CmsOrbit\Core\Screen\Field;
use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;
use Throwable;

/**
 * Class Rows.
 */
abstract class Rows extends Layout
{
    /**
     * @var string
     */
    protected $template = 'settings::layouts.row';

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
     * @throws Throwable
     *
     * @return Factory|\Illuminate\View\View
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (! $this->isSee()) {
            return;
        }

        $form = new Builder($this->fields(), $repository);

        return view($this->template, [
            'form'  => $form->generateForm(),
            'title' => $this->title,
        ]);
    }

    public function title(?string $title = null): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return iterable<Field>|iterable<string>
     */
    abstract protected function fields(): iterable;
}
