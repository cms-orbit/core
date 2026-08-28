<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Tests;

use CmsOrbit\Core\Foundation\Providers\FoundationServiceProvider;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use WithLaravelMigrations;

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [FoundationServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('scout.driver', null);
    }
}
