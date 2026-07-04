<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Fields;

use CmsOrbit\Core\Screen\Field;

/**
 * Colour picker field rendered by the React `color` component.
 */
class Color extends Field
{
    protected $view = 'orbit::fields.input';

    protected $attributes = [
        'class' => 'form-control',
        'value' => '#000000',
    ];

    protected $inlineAttributes = [
        'name',
        'placeholder',
        'readonly',
        'required',
        'tabindex',
        'value',
        'disabled',
    ];

    public function getComponent(): string
    {
        return 'color';
    }
}
