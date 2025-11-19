<?php

declare(strict_types=1);

namespace CmsOrbit\Core;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use CmsOrbit\Core\Support\Attributes\FlushOctaneState;

class Dashboard
{
    use Configuration\ManagesMenu,
        Configuration\ManagesModelOptions,
        Configuration\ManagesPackage,
        Configuration\ManagesPermissions,
        Configuration\ManagesResources,
        Configuration\ManagesScreens,
        Configuration\ManagesSearch,
        Macroable;

    /**
     * Get the route with the dashboard prefix.
     */
    public static function prefix(string $path = ''): string
    {
        $prefix = config('settings.prefix');

        return Str::start($prefix.$path, '/');
    }

    /**
     * Clear all persistent state information in the Orbit.
     *
     * This method is essential for Laravel Octane to properly handle stateful requests
     * when the Dashboard is used as a singleton. It ensures that any stored data
     * and state information are reset, avoiding potential issues with stale or
     * inconsistent data between requests.
     */
    public function flush(): void
    {
        $properties = (new \ReflectionClass($this))->getProperties();

        foreach ($properties as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if ($attribute->getName() !== FlushOctaneState::class) {
                    continue;
                }

                $property->setValue($this, $property->getDefaultValue());
            }
        }
    }
}
