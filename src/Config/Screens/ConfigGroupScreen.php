<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Config\Screens;

use CmsOrbit\Core\Config\ConfigFieldFactory;
use CmsOrbit\Core\Config\ConfigGroup;
use CmsOrbit\Core\Config\ConfigRegistry;
use CmsOrbit\Core\Config\ConfigSection;
use CmsOrbit\Core\Config\Layouts\ConfigFields;
use CmsOrbit\Core\Screen\Action;
use CmsOrbit\Core\Screen\Actions\Button;
use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Screen;
use CmsOrbit\Core\Support\Facades\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Auto-rendered edit screen for a single config group. Emits one Rows layout per
 * section with field nodes derived from each item's type.
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
        return [$this->group()->getPermission()];
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
}
