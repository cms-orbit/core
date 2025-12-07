<?php

declare(strict_types=1);

namespace CmsOrbit\Core\UI\Fields;

use CmsOrbit\Core\UI\Field;

/**
 * Class Label.
 *
 * @method $this name(string $value = null)
 * @method $this popover(string $value = null)
 * @method $this title(string $value = null)
 */
class Label extends Field
{
    /**
     * @var string
     */
    protected $view = 'orbit::fields.label';

    /**
     * Default attributes value.
     *
     * @var array
     */
    protected $attributes = [
        'id'    => null,
        'value' => null,
    ];

    /**
     * Attributes available for a particular tag.
     *
     * @var array
     */
    protected $inlineAttributes = [
        'class',
    ];
}
