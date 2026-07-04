<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Config\Screens;

use CmsOrbit\Core\Config\ConfigFieldFactory;
use CmsOrbit\Core\Config\ConfigGroup;
use CmsOrbit\Core\Config\ConfigRegistry;
use CmsOrbit\Core\Config\ConfigSection;
use CmsOrbit\Core\Config\Layouts\ConfigFields;
use CmsOrbit\Core\Config\LayoutThemeRegistry;
use CmsOrbit\Core\Foundation\Providers\ConfigServiceProvider;
use CmsOrbit\Core\Screen\Action;
use CmsOrbit\Core\Screen\Actions\Button;
use CmsOrbit\Core\Screen\Builder;
use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;
use CmsOrbit\Core\Screen\Screen;
use CmsOrbit\Core\Support\Facades\Layout as LayoutFactory;
use CmsOrbit\Core\Support\Facades\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Auto-rendered edit screen for a single config group. Emits one Rows layout per
 * section with field nodes derived from each item's type. The admin design
 * and SEO groups use dedicated React layouts with live previews.
 */
class ConfigGroupScreen extends Screen
{
    protected function group(): ConfigGroup
    {
        $group = app(ConfigRegistry::class)->getGroupByUriKey(request()->route('group'));

        abort_if($group === null, 404);

        return $group;
    }

    public function name(): ?string
    {
        return $this->group()->getTitle();
    }

    public function description(): ?string
    {
        return $this->group()->getDescription();
    }

    public function permission(): ?iterable
    {
        return $this->group()->accessiblePermissions();
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        $registry = app(ConfigRegistry::class);

        $values = [];
        foreach ($this->group()->getItems() as $item) {
            $values[ConfigFieldFactory::encodeKey($item->getKey())] = $registry->get($item->getKey());
        }

        return [
            ConfigFieldFactory::PREFIX => $values,
        ];
    }

    /**
     * @return Action[]
     */
    public function commandBar(): array
    {
        return [
            Button::make(__('Save'))->method('save')->icon('bs.check-circle'),
        ];
    }

    /**
     * @return Layout[]
     */
    public function layout(): array
    {
        $uriKey = $this->group()->getUriKey();

        if ($uriKey === 'admin-design') {
            $themeRegistry = app(LayoutThemeRegistry::class);

            return [
                LayoutFactory::component('design-settings', [
                    'fields'       => $this->serializeFields(ConfigServiceProvider::designSettingFieldKeys()),
                    'layoutModes'  => $themeRegistry->getModes(),
                    'layoutThemes' => $themeRegistry->getThemes(),
                ]),
            ];
        }

        if ($uriKey === 'seo') {
            return [
                LayoutFactory::component('seo-settings', [
                    'fields' => $this->serializeFields([
                        'seo.site_title',
                        'seo.title_separator',
                        'seo.site_description',
                        'seo.default_thumbnail',
                        'seo.snippet',
                        'seo.robots',
                    ]),
                ]),
            ];
        }

        $layouts = [];

        foreach ($this->group()->getSections() as $section) {
            /** @var ConfigSection $section */
            $fields = collect($section->getItems())
                ->map(fn ($item) => ConfigFieldFactory::make($item))
                ->values()
                ->all();

            if ($fields === []) {
                continue;
            }

            $layouts[] = new ConfigFields($fields, $section->getTitle());
        }

        return $layouts;
    }

    public function save(Request $request): RedirectResponse
    {
        $registry = app(ConfigRegistry::class);
        $data = (array) $request->input(ConfigFieldFactory::PREFIX, []);

        foreach ($data as $encoded => $value) {
            $registry->set(ConfigFieldFactory::decodeKey($encoded), $value);
        }

        Toast::info(__('Settings saved.'));

        return back();
    }

    /**
     * Serialize config items into React FieldNode arrays for custom layouts.
     *
     * @param string[] $keys
     *
     * @return array<int, array<string, mixed>>
     */
    protected function serializeFields(array $keys): array
    {
        $group = $this->group();
        $repository = new Repository($this->query());

        $fields = collect($keys)
            ->map(fn (string $key) => $group->getItem($key))
            ->filter()
            ->map(fn ($item) => ConfigFieldFactory::make($item))
            ->values()
            ->all();

        return (new Builder($fields, $repository))
            ->setPrefix(ConfigFieldFactory::PREFIX)
            ->generateArray();
    }
}
