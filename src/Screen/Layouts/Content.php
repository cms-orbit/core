<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;

abstract class Content extends Layout
{
    /**
     * @var Repository|null
     */
    protected $query;

    /**
     * Key property for a query.
     *
     * @var mixed
     */
    protected $target;

    /**
     * Card constructor.
     *
     * @param  mixed  $target
     */
    public function __construct($target)
    {
        $this->target = $target;
    }

    public function build(Repository $repository): string
    {
        $this->query = $repository;

        if (is_string($this->target) || is_array($this->target)) {
            $this->target = $repository->get($this->target, $this->target);
        }

        return (string) $this;
    }

    /**
     * Resolve the layout target from the repository (mirrors build()), without
     * mutating internal state, for serialization.
     *
     * @return mixed
     */
    protected function resolveTarget(Repository $repository)
    {
        $this->query = $repository;

        if (is_string($this->target) || is_array($this->target)) {
            return $repository->get($this->target, $this->target);
        }

        return $this->target;
    }

    public function __toString(): string
    {
        if (method_exists($this, 'render')) {
            return (string) $this->render($this->target);
        }

        throw new \RuntimeException('Method render not found');
    }
}
