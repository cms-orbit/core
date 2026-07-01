<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen;

use CmsOrbit\Core\Screen\Concerns\CanSee;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonSerializable;

/**
 * Class Layout.
 */
abstract class Layout implements JsonSerializable
{
    use CanSee;

    /**
     * Explicit serialized layout type. When null it is derived from the nearest
     * framework layout base class (e.g. a user Table subclass serializes as
     * "table"). Custom React layouts set this directly.
     *
     * @var string|null
     */
    protected $type;

    /**
     * The Main template to display the layer
     * Represents the view() argument.
     *
     * @var string
     */
    protected $template;

    /**
     * Nested layers that should be
     * displayed along with it.
     *
     * @var Layout[]
     */
    protected $layouts = [];

    /**
     * @var array
     */
    protected $variables = [];

    /**
     * @var Repository
     */
    protected $query;

    /**
     * @return mixed
     */
    abstract public function build(Repository $repository);

    /**
     * @return mixed
     */
    protected function buildAsDeep(Repository $repository)
    {
        $this->query = $repository;

        if (! $this->isSee()) {
            return;
        }

        $build = collect($this->layouts)
            ->map(fn ($layouts) => Arr::wrap($layouts))
            ->map(fn (iterable $layouts, string $key) => $this->buildChild($layouts, $key, $repository))
            ->collapse()
            ->all();

        $variables = array_merge($this->variables, [
            'templateSlug' => $this->getSlug(),
            'manyForms' => $build,
        ]);

        return view($this->template, $variables);
    }

    /**
     * @param  array  $layouts
     * @param  int|string  $key
     * @return array
     */
    protected function buildChild(iterable $layouts, $key, Repository $repository)
    {
        return collect($layouts)
            ->flatten()
            ->map(fn ($layout) => is_object($layout) ? $layout : resolve($layout))
            ->filter(fn (self $layout) => $layout->isSee())
            ->reduce(function ($build, self $layout) use ($key, $repository) {
                $build[$key][] = $layout->build($repository);

                return $build;
            }, []);
    }

    /**
     * Serialize this layout (and its children) to the React JSON contract
     * (LayoutNode). Mirrors buildAsDeep()'s recursion but emits a tree instead
     * of Blade views. Returns null when the layout is not visible.
     *
     * @return array<string, mixed>|null
     */
    public function toArray(Repository $repository): ?array
    {
        $this->query = $repository;

        if (! $this->isSee()) {
            return null;
        }

        return [
            'type' => $this->getType(),
            'key' => $this->getSlug(),
            'canSee' => true,
            'data' => $this->serialize($repository),
            'children' => $this->serializeChildren($repository),
        ];
    }

    /**
     * The React component key used to render this layout. Derived from the
     * nearest framework layout base class so that user subclasses serialize
     * under their base type (Table, Rows, …).
     */
    public function getType(): string
    {
        if ($this->type !== null) {
            return $this->type;
        }

        // Walk the class hierarchy with reflection so anonymous layouts created
        // by the LayoutFactory (e.g. Layout::rows()/table()) resolve to their
        // nearest framework base type. Anonymous classes are skipped because PHP
        // names them after their parent ("…\Layouts\Rows@anonymous /path:line"),
        // which would otherwise match the prefix and yield a junk basename.
        $reflection = new \ReflectionClass($this);

        while ($reflection !== false) {
            if (! $reflection->isAnonymous()
                && str_starts_with($reflection->getName(), __NAMESPACE__.'\\Layouts\\')) {
                return Str::of(class_basename($reflection->getName()))->kebab()->value();
            }

            $reflection = $reflection->getParentClass();
        }

        return Str::of(class_basename(static::class))->kebab()->value();
    }

    /**
     * The layout-specific data payload. Overridden by concrete layouts; the
     * default exposes any registered template variables.
     *
     * @return array<string, mixed>
     */
    protected function serialize(Repository $repository): array
    {
        return $this->variables;
    }

    /**
     * Recursively serialize nested layouts into a flat children list.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function serializeChildren(Repository $repository): array
    {
        return collect($this->layouts)
            ->flatten()
            ->map(fn ($layout) => is_object($layout) ? $layout : resolve($layout))
            ->filter(fn (self $layout) => $layout->isSee())
            ->map(fn (self $layout) => $layout->toArray($repository))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Returns the system layer name.
     * Required to define an asynchronous layer.
     */
    public function getSlug(): string
    {
        return hash('xxh3', json_encode($this));
    }

    /**
     * @return Layout|null
     */
    public function findBySlug(string $slug)
    {
        if ($slug === $this->getSlug()) {
            return $this;
        }

        // Trying to find the right layer inside
        return collect($this->layouts)
            ->flatten()
            ->map(static function ($layout) use ($slug) {
                $layout = is_object($layout)
                    ? $layout
                    : resolve($layout);

                return $layout->findBySlug($slug);
            })
            ->filter()
            ->filter(static fn ($layout) => $slug === $layout->getSlug())
            ->first();
    }

    /**
     * @return Layout|null
     */
    public function findByType(string $type)
    {
        if (is_subclass_of($this, $type)) {
            return $this;
        }

        // Trying to find the right layer inside
        return collect($this->layouts)
            ->flatten()
            ->map(fn ($layout) => is_object($layout) ? $layout : resolve($layout))
            ->map(fn (Layout $layout) => $layout->findByType($type))
            ->filter()
            ->first();
    }

    public function jsonSerialize(): array
    {
        $props = collect(get_object_vars($this));

        return $props->except(['query'])->toArray();
    }
}
