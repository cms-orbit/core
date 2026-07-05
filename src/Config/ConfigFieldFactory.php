<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Config;

use CmsOrbit\Core\Screen\Field;
use CmsOrbit\Core\Screen\Fields\Attach;
use CmsOrbit\Core\Screen\Fields\Color;
use CmsOrbit\Core\Screen\Fields\Cropper;
use CmsOrbit\Core\Screen\Fields\Input;
use CmsOrbit\Core\Screen\Fields\Select;
use CmsOrbit\Core\Screen\Fields\Switcher;
use CmsOrbit\Core\Screen\Fields\TextArea;
use Illuminate\Support\Facades\Route;

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
            'textarea'    => TextArea::make($name)->rows(4),
            'select'      => Select::make($name)->options(self::options($item)),
            'multiselect' => Select::make($name)->options(self::options($item))->multiple(),
            'switcher'    => Switcher::make($name)->sendTrueOrFalse(),
            'number'      => Input::make($name)->type('number'),
            'secret'      => Input::make($name)->type('password')->autocomplete('new-password'),
            'color'       => Color::make($name),
            'attach'      => self::makeAttachField($name),
            default       => Input::make($name),
        };

        $field = $field
            ->title($title)
            ->help($help);

        $visibleWhen = $item->getAttribute('visibleWhen');

        if (is_array($visibleWhen) && $visibleWhen !== []) {
            $field->set('visibleWhen', collect($visibleWhen)
                ->mapWithKeys(fn ($value, string $key): array => [self::encodeKey($key) => $value])
                ->all());
        }

        $customizer = $item->getAttribute('field');

        if ($customizer instanceof \Closure) {
            $customized = $customizer($field, $item);

            if ($customized instanceof Field) {
                $field = $customized;
            }
        }

        return $field;
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

    protected static function makeAttachField(string $name): Field
    {
        if (str_contains($name, 'branding__logo') || str_contains($name, 'branding__symbol') || str_contains($name, 'branding__favicon')) {
            return self::makeBrandImageField($name);
        }

        $field = Attach::make($name)
            ->accept('image/*')
            ->set('returnObjects', true);

        if (str_contains($name, 'branding__favicon')) {
            $field
                ->group('branding')
                ->set('purpose', 'favicon')
                ->set('crop', true)
                ->set('placeholder', 'PNG 파비콘 업로드');
        } elseif (str_contains($name, 'branding__logo') || str_contains($name, 'branding__symbol')) {
            $field
                ->group('branding')
                ->set('crop', true)
                ->set('placeholder', '브랜드 이미지 업로드');
        }

        if (Route::has('orbit.media.upload')) {
            $field->uploadUrl(route('orbit.media.upload'));
        }

        return $field;
    }

    protected static function makeBrandImageField(string $name): Field
    {
        /** @var Cropper $field */
        $field = Cropper::make($name)
            ->targetId()
            ->set('returnObjects', true)
            ->set('group', 'branding')
            ->set('groups', 'branding')
            ->acceptedFiles('image/png,image/jpeg,image/webp,image/svg+xml');

        if (str_contains($name, 'branding__favicon')) {
            $field
                ->width(512)
                ->height(512)
                ->minCanvas(192)
                ->maxCanvas(1024)
                ->set('purpose', 'favicon')
                ->placeholder('PNG 파비콘 업로드');
        } elseif (str_contains($name, 'branding__symbol')) {
            $field
                ->width(512)
                ->height(512)
                ->minCanvas(192)
                ->maxCanvas(1024)
                ->set('purpose', 'symbol')
                ->placeholder('심볼 이미지 업로드');
        } else {
            $field
                ->maxWidth(2400)
                ->maxHeight(1200)
                ->set('purpose', 'logo')
                ->placeholder('로고 이미지 업로드');
        }

        if (Route::has('orbit.media.upload')) {
            $field->uploadUrl(route('orbit.media.upload'));
        }

        return $field;
    }
}
