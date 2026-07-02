<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Config;

use CmsOrbit\Core\Screen\Field;
use CmsOrbit\Core\Screen\Fields\Attach;
use CmsOrbit\Core\Screen\Fields\Input;
use CmsOrbit\Core\Screen\Fields\Select;
use CmsOrbit\Core\Screen\Fields\Switcher;
use CmsOrbit\Core\Screen\Fields\TextArea;

/**
 * Maps a ConfigItem type to a concrete Field for the auto-rendered group-edit
 * screen. Item keys contain dots; they are flattened to a "__" form so the
 * serialized form payload stays flat (see ConfigGroupScreen).
 */
class ConfigFieldFactory
{
    public const PREFIX = 'config';

    /**
     * Encode a dotted config key into a flat form field name.
     */
    public static function encodeKey(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    /**
     * Decode a flat form field name back to its dotted config key.
     */
    public static function decodeKey(string $name): string
    {
        return str_replace('__', '.', $name);
    }

    /**
     * Build a bound field for a config item.
     */
    public static function make(ConfigItem $item): Field
    {
        $name = self::encodeKey($item->getKey());
        $title = $item->getTitle();
        $help = $item->getDescription();

        $field = match ($item->getType()) {
            'textarea' => TextArea::make($name)->rows(4),
            'select' => Select::make($name)->options(self::options($item)),
            'multiselect' => Select::make($name)->options(self::options($item))->multiple(),
            'switcher' => Switcher::make($name)->sendTrueOrFalse(),
            'number' => Input::make($name)->type('number'),
            'color' => Input::make($name)->type('color'),
            'attach' => Attach::make($name),
            default => Input::make($name),
        };

        return $field
            ->title($title)
            ->help($help);
    }

    /**
     * Normalise the item options into a key => label map for Select fields.
     *
     * @return array<int|string, mixed>
     */
    protected static function options(ConfigItem $item): array
    {
        $options = $item->getAttribute('options', []);

        if (array_is_list($options)) {
            return array_combine($options, $options);
        }

        return $options;
    }
}
