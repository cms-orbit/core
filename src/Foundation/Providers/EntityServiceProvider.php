<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Providers;

use CmsOrbit\Core\Entities\DemoEntity;
use CmsOrbit\Core\Entities\RoleEntity;
use CmsOrbit\Core\Entities\UserEntity;
use CmsOrbit\Core\Foundation\Entity\EntityRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the Entity registry, seeds the default root /entities scan path, and
 * submits every entity's permissions and menu to Core once the app has booted.
 */
class EntityServiceProvider extends ServiceProvider
{
    /**
     * Admin entities shipped with the package itself.
     *
     * @var array<int, class-string<\CmsOrbit\Core\Foundation\Entity\Entity>>
     */
    protected array $entities = [
        UserEntity::class,
        RoleEntity::class,
    ];

    public function register(): void
    {
        $this->app->singleton(EntityRegistry::class, function () {
            $registry = new EntityRegistry;

            $entities = $this->entities;

            if ((bool) config('orbit.demo.enabled', false)) {
                $entities[] = DemoEntity::class;
            }

            $registry
                ->registerClass($entities)
                ->registerPath(base_path('entities'), 'Entities\\');

            return $registry;
        });
    }

    public function boot(): void
    {
        $this->app->booted(function () {
            app(EntityRegistry::class)->boot();
        });
    }
}
