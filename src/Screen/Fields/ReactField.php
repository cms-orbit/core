<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Fields;

use CmsOrbit\Core\Screen\Field;

/**
 * Class ReactField.
 *
 * Field-level escape hatch: bind a single field slot to a custom React
 * component resolved client-side via the registry (registerComponents).
 *
 * Example:
 *   ReactField::make('color')->component('ColorPicker')->props(['palette' => [...]]);
 */
class ReactField extends Field
{
    /**
     * @var array
     */
    protected $attributes = [
        'value' => null,
    ];

    /**
     * Set the custom React component name to render this field.
     */
    public function component(string $component): static
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Pass arbitrary props to the custom React component.
     *
     * @param  array<string, mixed>  $props
     */
    public function props(array $props): static
    {
        return $this->set('props', $props);
    }

    /**
     * Serialize, attaching custom props under the `props` key.
     *
     * @return array<string, mixed>|null
     */
    public function toArray(): ?array
    {
        $node = parent::toArray();

        if ($node !== null) {
            $node['props'] = $this->get('props', []);
        }

        return $node;
    }
}
