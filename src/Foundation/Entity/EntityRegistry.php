<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Entity;

use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Central registry that collects Entity descriptors submitted by the host
 * application (root /entities) and by external packages, then exposes them to
 * the CRUD engine, menu, permissions and routing.
 */
class EntityRegistry
{
    /**
     * Explicitly registered entity class names.
     *
     * @var array<int, class-string<Entity>>
     */
    protected array $classes = [];

    /**
     * Directories to scan, mapped path => PSR-4 namespace prefix.
     *
     * @var array<string, string>
     */
    protected array $paths = [];

    /**
     * Resolved entity instances keyed by uriKey (lazily built).
     *
     * @var Collection<string, Entity>|null
     */
    protected ?Collection $resolved = null;

    /**
     * Register a directory to scan for Entity descriptors.
     */
    public function registerPath(string $path, string $namespace = 'Entities\\'): static
    {
        $this->paths[rtrim($path, '/')] = rtrim($namespace, '\\').'\\';
        $this->resolved = null;

        return $this;
    }

    /**
     * Register one or more Entity class names directly.
     *
     * @param  class-string<Entity>|array<int, class-string<Entity>>  $class
     */
    public function registerClass(string|array $class): static
    {
        foreach ((array) $class as $entity) {
            if (! in_array($entity, $this->classes, true)) {
                $this->classes[] = $entity;
            }
        }

        $this->resolved = null;

        return $this;
    }

    /**
     * Smart entry point used by Orbit::registerEntities(): accepts a directory
     * path or one/many Entity class names.
     *
     * @param  string|array<int, string>  $pathOrClass
     */
    public function register(string|array $pathOrClass): static
    {
        foreach ((array) $pathOrClass as $item) {
            if (is_dir($item)) {
                $this->registerPath($item);

                continue;
            }

            if (class_exists($item)) {
                $this->registerClass($item);
            }
        }

        return $this;
    }

    /**
     * All resolved entity instances keyed by uriKey, sorted by sort + label.
     *
     * @return Collection<string, Entity>
     */
    public function all(): Collection
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $classes = collect($this->classes)
            ->merge($this->scan())
            ->unique()
            ->filter(fn (string $class) => is_subclass_of($class, Entity::class)
                && ! (new ReflectionClass($class))->isAbstract());

        return $this->resolved = $classes
            ->map(fn (string $class) => app($class))
            ->sort(fn (Entity $a, Entity $b) => [$a->sort(), $a->label()] <=> [$b->sort(), $b->label()])
            ->keyBy(fn (Entity $entity) => $entity::uriKey());
    }

    /**
     * Find an entity by its uriKey.
     */
    public function find(string $uriKey): ?Entity
    {
        return $this->all()->get($uriKey);
    }

    /**
     * Find an entity by uriKey or abort 404.
     */
    public function findOrFail(string $uriKey): Entity
    {
        $entity = $this->find($uriKey);

        abort_if($entity === null, 404);

        return $entity;
    }

    /**
     * Submit every registered entity's permissions and menu to Core.
     */
    public function boot(): void
    {
        $this->all()->each(function (Entity $entity) {
            Orbit::registerPermission($entity->permissions());

            foreach ($entity->menu() as $menu) {
                Orbit::registerMenuElement($menu);
            }
        });
    }

    /**
     * Discover Entity classes from the registered scan paths.
     *
     * @return array<int, class-string<Entity>>
     */
    protected function scan(): array
    {
        $found = [];

        foreach ($this->paths as $path => $namespace) {
            if (! is_dir($path)) {
                continue;
            }

            $finder = (new Finder)
                ->files()
                ->name('*.php')
                ->ignoreUnreadableDirs()
                ->in($path);

            foreach ($finder as $file) {
                $class = $this->resolveClass($path, $namespace, $file);

                if (class_exists($class)) {
                    $found[] = $class;
                }
            }
        }

        return $found;
    }

    /**
     * Map a discovered file back to its PSR-4 class name.
     */
    protected function resolveClass(string $path, string $namespace, SplFileInfo $file): string
    {
        $relative = Str::of($file->getPathname())
            ->after($path.DIRECTORY_SEPARATOR)
            ->replace(DIRECTORY_SEPARATOR, '\\')
            ->replaceLast('.php', '');

        return $namespace.$relative;
    }
}
