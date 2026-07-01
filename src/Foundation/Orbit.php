<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation;

use CmsOrbit\Core\Support\Attributes\FlushOctaneState;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;

class Orbit
{
    use Configuration\ManagesConfig,
        Configuration\ManagesEntities,
        Configuration\ManagesMenu,
        Configuration\ManagesModelOptions,
        Configuration\ManagesPackage,
        Configuration\ManagesPermissions,
        Configuration\ManagesResources,
        Configuration\ManagesScreens,
        Configuration\ManagesSearch,
        Macroable;

    /**
     * Get the route with the orbit prefix.
     *
     * Only "path" access mode applies a URL prefix; subdomain/domain modes
     * serve the panel from the domain root.
     */
    public static function prefix(string $path = ''): string
    {
        $prefix = config('orbit.access.mode') === 'path'
            ? '/'.trim((string) config('orbit.access.prefix', 'settings'), '/')
            : '';

        return Str::start($prefix.$path, '/');
    }

    /**
     * Clear all persistent state information in the Orbit.
     *
     * This method is essential for Laravel Octane to properly handle stateful requests
     * when the Orbit is used as a singleton. It ensures that any stored data
     * and state information are reset, avoiding potential issues with stale or
     * inconsistent data between requests.
     */
    public function flush(): void
    {
        $properties = (new \ReflectionClass($this))->getProperties();

        foreach ($properties as $property) {
            if ($property->getAttributes(FlushOctaneState::class)) {
                $property->setValue($this, $property->getDefaultValue());
            }
        }
    }
}
