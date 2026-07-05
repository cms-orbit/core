<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Fields;

use CmsOrbit\Core\Screen\Field;

/**
 * Notion-style block rich text editor (BlockNote) rendered in React.
 *
 * @method $this autofocus($value = true)
 * @method $this disabled($value = true)
 * @method $this form($value = true)
 * @method $this formaction($value = true)
 * @method $this formenctype($value = true)
 * @method $this formmethod($value = true)
 * @method $this formnovalidate($value = true)
 * @method $this formtarget($value = true)
 * @method $this name(string $value = null)
 * @method $this placeholder(string $value = null)
 * @method $this readonly($value = true)
 * @method $this required(bool $value = true)
 * @method $this tabindex($value = true)
 * @method $this value($value = true)
 * @method $this help(string $value = null)
 * @method $this height($value = '300px')
 * @method $this title(string $value = null)
 * @method $this popover(string $value = null)
 */
class RichText extends Field
{
    /**
     * @var string
     */
    protected $view = 'orbit::fields.rich-text';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'value' => null,
        'height' => '300px',
    ];

    /**
     * @var array<int, string>
     */
    protected $inlineAttributes = [
        'accesskey',
        'autocomplete',
        'autofocus',
        'checked',
        'disabled',
        'form',
        'formaction',
        'formenctype',
        'formmethod',
        'formnovalidate',
        'formtarget',
        'name',
        'placeholder',
        'readonly',
        'required',
        'step',
        'tabindex',
        'height',
    ];
}
