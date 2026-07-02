<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Screen\Field;
use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\LayoutFactory;
use CmsOrbit\Core\Screen\Repository;
use CmsOrbit\Core\Support\Locale;

/**
 * Renders a set of translatable fields once per content locale as tabs.
 *
 * Each locale tab clones the supplied fields and scopes their `name` to the
 * locale (via the field `lang` attribute), so the form submits values under a
 * locale key, e.g. `ko[title]` / `en[title]`. Consumers read them back with
 * dot notation (`$request->input('ko.title')`) or a translatable model cast.
 *
 * Usage inside an Entity or Screen layout:
 *   LocaleTabs::make([
 *       Input::make('title')->title(__('Title')),
 *       Quill::make('content')->title(__('Content')),
 *   ])
 */
class LocaleTabs extends Layout
{
    /**
     * @var string
     */
    protected $type = 'locale-tabs';

    /**
     * @param  Field[]  $fields
     * @param  array<int, string>|null  $locales  Content locales (defaults to config).
     */
    public function __construct(
        protected array $fields = [],
        protected ?array $locales = null,
    ) {}

    /**
     * @param  Field[]  $fields
     */
    public static function make(array $fields = [], ?array $locales = null): static
    {
        return new static($fields, $locales);
    }

    public function build(Repository $repository)
    {
        return $this->buildAsDeep($repository);
    }

    /**
     * The resolved list of content locales, always with at least one entry.
     *
     * @return array<int, string>
     */
    protected function contentLocales(): array
    {
        $locales = $this->locales ?? Locale::content();

        return $locales === [] ? [Locale::default()] : $locales;
    }

    /**
     * @return array<string, mixed>
     */
    protected function serialize(Repository $repository): array
    {
        $locales = $this->contentLocales();

        return [
            'titles' => array_map(fn (string $code) => Locale::label($code), $locales),
            'locales' => array_map(fn (string $code) => [
                'code' => $code,
                'label' => Locale::label($code),
            ], $locales),
            'activeTab' => in_array(app()->getLocale(), $locales, true)
                ? app()->getLocale()
                : ($locales[0] ?? null),
        ];
    }

    /**
     * One `tab-pane` per locale, each wrapping locale-scoped clones of the
     * supplied fields.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function serializeChildren(Repository $repository): array
    {
        return collect($this->contentLocales())
            ->map(function (string $code) use ($repository) {
                $fields = collect($this->fields)
                    ->filter(fn ($field) => $field instanceof Field)
                    ->map(fn (Field $field) => (clone $field)->set('lang', $code))
                    ->all();

                $pane = LayoutFactory::rows($fields)->toArray($repository);

                return [
                    'type' => 'tab-pane',
                    'key' => $code,
                    'canSee' => true,
                    'data' => ['title' => Locale::label($code), 'locale' => $code],
                    'children' => $pane !== null ? [$pane] : [],
                ];
            })
            ->values()
            ->all();
    }
}
