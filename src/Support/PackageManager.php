<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Support;

use CmsOrbit\Core\Support\Attributes\FlushOctaneState;

/**
 * Package Manager
 * 
 * 외부 패키지가 자원을 등록할 수 있는 중앙 관리 시스템
 */
class PackageManager
{
    /**
     * Registered paths
     *
     * @var array<string, string>
     */
    #[FlushOctaneState]
    protected array $paths = [];

    /**
     * Registered assets
     *
     * @var array<string, array>
     */
    #[FlushOctaneState]
    protected array $assets = [];

    /**
     * Registered Vite entries
     *
     * @var array<string>
     */
    #[FlushOctaneState]
    protected array $viteEntries = [];

    /**
     * Registered Tailwind content paths
     *
     * @var array<string>
     */
    #[FlushOctaneState]
    protected array $tailwindContents = [];

    /**
     * Registered view namespaces
     *
     * @var array<string, string>
     */
    #[FlushOctaneState]
    protected array $viewNamespaces = [];

    /**
     * Registered fields
     *
     * @var array<string, string>
     */
    #[FlushOctaneState]
    protected array $fields = [];

    /**
     * Registered entities
     *
     * @var array<string, string>
     */
    #[FlushOctaneState]
    protected array $entities = [];

    /**
     * Registered entity paths
     *
     * @var array<string>
     */
    #[FlushOctaneState]
    protected array $entityPaths = [];

    /**
     * Registered frontend scripts
     *
     * @var array<string, string>
     */
    #[FlushOctaneState]
    protected array $frontendScripts = [];

    /**
     * Register a path alias
     *
     * @param string $alias
     * @param string $path
     * @return void
     */
    public function registerPath(string $alias, string $path): void
    {
        $this->paths[$alias] = $path;
    }

    /**
     * Get all registered paths
     *
     * @return array<string, string>
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * Register assets for a package
     *
     * @param string $package
     * @param array $assets
     * @return void
     */
    public function registerAsset(string $package, array $assets): void
    {
        $this->assets[$package] = $assets;
    }

    /**
     * Get registered assets
     *
     * @param string|null $package
     * @return array
     */
    public function getAssets(?string $package = null): array
    {
        if ($package) {
            return $this->assets[$package] ?? [];
        }

        return $this->assets;
    }

    /**
     * Register a Vite entry point
     *
     * @param string $entry
     * @return void
     */
    public function registerViteEntry(string $entry): void
    {
        if (!in_array($entry, $this->viteEntries)) {
            $this->viteEntries[] = $entry;
        }
    }

    /**
     * Get all Vite entries
     *
     * @return array<string>
     */
    public function getViteEntries(): array
    {
        return $this->viteEntries;
    }

    /**
     * Register a Tailwind content path
     *
     * @param string $path
     * @return void
     */
    public function registerTailwindContent(string $path): void
    {
        if (!in_array($path, $this->tailwindContents)) {
            $this->tailwindContents[] = $path;
        }
    }

    /**
     * Get all Tailwind content paths
     *
     * @return array<string>
     */
    public function getTailwindContents(): array
    {
        return $this->tailwindContents;
    }

    /**
     * Register a view namespace
     *
     * @param string $namespace
     * @param string $path
     * @return void
     */
    public function registerViewNamespace(string $namespace, string $path): void
    {
        $this->viewNamespaces[$namespace] = $path;
        
        // Register with Laravel's view system
        view()->addNamespace($namespace, $path);
    }

    /**
     * Get all view namespaces
     *
     * @return array<string, string>
     */
    public function getViewNamespaces(): array
    {
        return $this->viewNamespaces;
    }

    /**
     * Register a custom field
     *
     * @param string $name
     * @param string $class
     * @return void
     */
    public function registerField(string $name, string $class): void
    {
        $this->fields[$name] = $class;
    }

    /**
     * Get all registered fields
     *
     * @return array<string, string>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Register an entity
     *
     * @param string $name
     * @param string $class
     * @return void
     */
    public function registerEntity(string $name, string $class): void
    {
        $this->entities[$name] = $class;
    }

    /**
     * Get all registered entities
     *
     * @return array<string, string>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * Register an entity path for discovery
     *
     * @param string $path
     * @return void
     */
    public function registerEntityPath(string $path): void
    {
        if (!in_array($path, $this->entityPaths)) {
            $this->entityPaths[] = $path;
        }
    }

    /**
     * Get all registered entity paths
     *
     * @return array<string>
     */
    public function getEntityPaths(): array
    {
        return $this->entityPaths;
    }

    /**
     * Register a frontend script
     *
     * @param string $key
     * @param string $script
     * @return void
     */
    public function registerFrontendScript(string $key, string $script): void
    {
        $this->frontendScripts[$key] = $script;
    }

    /**
     * Get all frontend scripts
     *
     * @return array<string, string>
     */
    public function getFrontendScripts(): array
    {
        return $this->frontendScripts;
    }

    /**
     * Generate Vite configuration array
     *
     * @return array
     */
    public function generateViteConfig(): array
    {
        $config = [];

        // Add path aliases
        if (!empty($this->paths)) {
            $config['resolve'] = [
                'alias' => $this->paths,
            ];
        }

        // Add input entries
        if (!empty($this->viteEntries)) {
            $config['input'] = $this->viteEntries;
        }

        return $config;
    }

    /**
     * Generate Tailwind configuration array
     *
     * @return array
     */
    public function generateTailwindConfig(): array
    {
        return [
            'content' => $this->tailwindContents,
        ];
    }

    /**
     * Clear all registered data (for Laravel Octane)
     *
     * @return void
     */
    public function flush(): void
    {
        $this->paths = [];
        $this->assets = [];
        $this->viteEntries = [];
        $this->tailwindContents = [];
        $this->viewNamespaces = [];
        $this->fields = [];
        $this->entities = [];
        $this->entityPaths = [];
        $this->frontendScripts = [];
    }
}

