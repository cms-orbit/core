<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;

/**
 * Class Component.
 *
 * Renders a custom React component as a layout node (escape hatch). The
 * component name becomes the serialized layout `type`, resolved client-side via
 * the registry (registerComponents). Props are passed through `data.props`.
 */
abstract class Component extends Layout
{
    /**
     * @var string
     */
    private $component;

    private array $data = [];

    /**
     * Component constructor.
     */
    public function __construct(string $component)
    {
        $this->component = $component;
    }

    /**
     * Legacy Blade build path is unused; rendering happens via toArray().
     */
    public function build(Repository $repository)
    {
        return null;
    }

    public function getType(): string
    {
        return $this->component;
    }

    /**
     * @return array<string, mixed>
     */
    protected function serialize(Repository $repository): array
    {
        return [
            'component' => $this->component,
            'props' => $this->data,
        ];
    }

    public function with(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }
}
