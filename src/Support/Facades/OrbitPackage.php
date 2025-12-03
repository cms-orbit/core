<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Orbit Package Facade
 * 
 * 외부 패키지가 자원을 등록할 수 있는 Facade
 * 
 * @method static void registerPath(string $alias, string $path)
 * @method static array getPaths()
 * @method static void registerAsset(string $package, array $assets)
 * @method static array getAssets(?string $package = null)
 * @method static void registerViteEntry(string $entry)
 * @method static array getViteEntries()
 * @method static void registerTailwindContent(string $path)
 * @method static array getTailwindContents()
 * @method static void registerViewNamespace(string $namespace, string $path)
 * @method static array getViewNamespaces()
 * @method static void registerField(string $name, string $class)
 * @method static array getFields()
 * @method static void registerEntity(string $name, string $class)
 * @method static array getEntities()
 * @method static void registerFrontendScript(string $key, string $script)
 * @method static array getFrontendScripts()
 * @method static array generateViteConfig()
 * @method static array generateTailwindConfig()
 * @method static void flush()
 *
 * @see \CmsOrbit\Core\Support\PackageManager
 */
class OrbitPackage extends Facade
{
    /**
     * Get the registered name of the component
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \CmsOrbit\Core\Support\PackageManager::class;
    }
}

