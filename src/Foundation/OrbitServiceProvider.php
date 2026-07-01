<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation;

use Illuminate\Support\ServiceProvider;

/**
 * Base provider extended by the host application's OrbitProvider (and by
 * external content packages). Subclasses submit entities, config groups, menu
 * and permissions to Core from their boot() method using the Orbit / Config
 * facades.
 *
 * Example:
 *
 *   class OrbitProvider extends OrbitServiceProvider
 *   {
 *       public function boot(): void
 *       {
 *           Orbit::registerEntities(base_path('entities'));
 *           Config::registerGroup('Newsletter', 500);
 *       }
 *   }
 */
abstract class OrbitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        //
    }
}
