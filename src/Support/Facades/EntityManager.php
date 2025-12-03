<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Support\Facades;

use Illuminate\Support\Facades\Facade;
use CmsOrbit\Core\Support\EntityDiscovery;

/**
 * @method static void registerPath(string $path)
 * @method static \Illuminate\Support\Collection discover()
 * @method static \Illuminate\Support\Collection getRouteFiles()
 * @method static \Illuminate\Support\Collection getMigrationPaths()
 * @method static \Illuminate\Support\Collection getMenuItems()
 *
 * @see EntityDiscovery
 */
class EntityManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EntityDiscovery::class;
    }
}

