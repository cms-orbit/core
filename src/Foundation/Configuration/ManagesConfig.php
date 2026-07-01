<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Configuration;

use CmsOrbit\Core\Config\ConfigGroup;
use CmsOrbit\Core\Config\ConfigItem;
use CmsOrbit\Core\Config\ConfigRegistry;
use CmsOrbit\Core\Config\ConfigSection;

trait ManagesConfig
{
    /**
     * Register a configuration group (convenience proxy to the Config facade /
     * ConfigRegistry; external packages may use either entry point).
     */
    public function registerConfigGroup(string $name, int $priority = 0, array $attributes = []): ConfigGroup
    {
        return app(ConfigRegistry::class)->registerGroup($name, $priority, $attributes);
    }

    public function registerConfigSection(string $groupName, string $sectionName, array $attributes = []): ConfigSection
    {
        return app(ConfigRegistry::class)->registerSection($groupName, $sectionName, $attributes);
    }

    public function registerConfigItem(
        string $groupName,
        string $key,
        string $type,
        mixed $default = null,
        string $sectionName = 'default',
        array $attributes = [],
    ): ConfigItem {
        return app(ConfigRegistry::class)->registerItem($groupName, $key, $type, $default, $sectionName, $attributes);
    }

    /**
     * Resolve a stored configuration value (with CoR fallback).
     */
    public function config(string $key, mixed $default = null, ?int $instanceId = null): mixed
    {
        return app(ConfigRegistry::class)->get($key, $default, $instanceId);
    }
}
