<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Support\Facades;

use CmsOrbit\Core\Config\ConfigGroup;
use CmsOrbit\Core\Config\ConfigItem;
use CmsOrbit\Core\Config\ConfigRegistry;
use CmsOrbit\Core\Config\ConfigSection;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Orbit configuration registry. The submission API used by
 * external packages in their ServiceProvider::boot().
 *
 * @method static ConfigGroup registerGroup(string $name, int $priority = 0, array $attributes = [])
 * @method static ConfigSection registerSection(string $groupName, string $sectionName, array $attributes = [])
 * @method static ConfigItem registerItem(string $groupName, string $key, string $type, mixed $default = null, string $sectionName = 'default', array $attributes = [])
 * @method static array getGroups()
 * @method static ConfigGroup|null getGroup(string $name)
 * @method static ConfigGroup|null getGroupByUriKey(string $uriKey)
 * @method static ConfigItem|null findItem(string $key)
 * @method static mixed get(string $key, mixed $default = null, ?int $instanceId = null)
 * @method static void set(string $key, mixed $value, ?int $instanceId = null)
 */
class Config extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ConfigRegistry::class;
    }
}
