<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Screen\Builder;
use CmsOrbit\Core\Screen\Field;
use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;
use Illuminate\Contracts\View\Factory;
use Illuminate\View\View;
use Throwable;

/**
 * Class Rows.
 */
abstract class Rows extends Layout
{
    /**
     * @var string
     */
    protected $template = 'orbit::layouts.row';

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
     * @return Factory|View
     *
     * @throws Throwable
     */
    public function build(Repository $repository)
    {
        $this->query = $repository;

        if (! $this->isSee()) {
            return;
        }

        $form = new Builder($this->fields(), $repository);

        return view($this->template, [
            'form' => $form->generateForm(),
            'title' => $this->title,
        ]);
    }

    public function title(?string $title = null): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function serialize(Repository $repository): array
    {
        return [
            'title' => $this->title,
            'fields' => (new Builder($this->fields(), $repository))->generateArray(),
        ];
    }

    /**
     * @return iterable<Field>|iterable<string>
     */
    abstract protected function fields(): iterable;
}
