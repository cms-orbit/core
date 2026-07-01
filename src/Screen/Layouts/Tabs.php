<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;
use Illuminate\Support\Arr;

/**
 * Class Tabs.
 */
abstract class Tabs extends Layout
{
    /**
     * @var string
     */
    public $template = 'orbit::layouts.tabs';

    /**
     * @var array
     */
    protected $variables = [
        'activeTab' => null,
    ];

    /**
     * Layout constructor.
     *
     * @param  Layout[]  $layouts
     */
    public function __construct(array $layouts = [])
    {
        $this->layouts = $layouts;
    }

    /**
     * @return mixed
     */
    public function build(Repository $repository)
    {
        return $this->buildAsDeep($repository);
    }

    /**
     * @return $this
     */
    public function activeTab(string $name)
    {
        $this->variables['activeTab'] = $name;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function serialize(Repository $repository): array
    {
        return [
            'titles' => array_values(array_map(static fn ($k) => is_string($k) ? $k : null, array_keys($this->layouts))),
            'activeTab' => $this->variables['activeTab'] ?? null,
        ];
    }

    /**
     * Each tab is wrapped in a "tab-pane" node so React can map
     * titles[i] -> children[i].
     *
     * @return array<int, array<string, mixed>>
     */
    protected function serializeChildren(Repository $repository): array
    {
        return collect($this->layouts)
            ->map(function ($layouts, $title) use ($repository) {
                $panes = collect(Arr::wrap($layouts))
                    ->flatten()
                    ->map(fn ($layout) => is_object($layout) ? $layout : resolve($layout))
                    ->filter(fn (Layout $layout) => $layout->isSee())
                    ->map(fn (Layout $layout) => $layout->toArray($repository))
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'type' => 'tab-pane',
                    'key' => (string) $title,
                    'canSee' => true,
                    'data' => ['title' => is_string($title) ? $title : null],
                    'children' => $panes,
                ];
            })
            ->values()
            ->all();
    }
}
