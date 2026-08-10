<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Providers;

use CmsOrbit\Core\Entities\ActivityEntity;
use CmsOrbit\Core\Entities\DemoEntity;
use CmsOrbit\Core\Entities\LoginHistoryEntity;
use CmsOrbit\Core\Entities\RoleEntity;
use CmsOrbit\Core\Entities\UserAccountEntity;
use CmsOrbit\Core\Entities\UserEntity;
use CmsOrbit\Core\Entities\VisitorRecordEntity;
use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Foundation\Entity\EntityRegistry;
use CmsOrbit\Core\Screen\Actions\Menu;
use CmsOrbit\Core\Support\Facades\Orbit;
use Composer\Autoload\ClassLoader;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the Entity registry, seeds the default root /entities scan path, and
 * submits every entity's permissions and menu to Core once the app has booted.
 */
class EntityServiceProvider extends ServiceProvider
{
    protected const ACCESS_CONTROL_IDENTITY_GROUP = 'access-control-identity';

    protected const ACCESS_CONTROL_RECORDS_GROUP = 'access-control-records';

    /**
     * Admin entities shipped with the package itself.
     *
     * @var array<int, class-string<Entity>>
     */
    protected array $entities = [
        UserEntity::class,
        UserAccountEntity::class,
        RoleEntity::class,
        ActivityEntity::class,
        LoginHistoryEntity::class,
        VisitorRecordEntity::class,
    ];

    public function register(): void
    {
        $this->registerHostEntitiesAutoload();

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

    /**
     * Register a runtime PSR-4 prefix for host {@see base_path('entities')} so
     * descriptors work without a host composer.json autoload entry.
     */
    protected function registerHostEntitiesAutoload(): void
    {
        $path = base_path('entities');

        if (! is_dir($path)) {
            return;
        }

        foreach (spl_autoload_functions() ?: [] as $autoload) {
            if (! is_array($autoload) || ! ($autoload[0] instanceof ClassLoader)) {
                continue;
            }

            $autoload[0]->addPsr4('Entities\\', $path.DIRECTORY_SEPARATOR);

            return;
        }
    }

    public function boot(): void
    {
        Orbit::registerSection('access-control', 'bs.people-fill', fn () => __('Users & Roles'), 1000);
        $this->registerAccessControlGroups();

        $this->app->booted(function () {
            app(EntityRegistry::class)->boot();
        });
    }

    protected function registerAccessControlGroups(): void
    {
        Orbit::registerMenuElement(
            Menu::make(__('Identity'))
                ->slug(self::ACCESS_CONTROL_IDENTITY_GROUP)
                ->sort(1000)
                ->set('section', __('Users & Roles'))
                ->set('sectionKey', 'access-control')
                ->set('permission', [
                    app(UserEntity::class)->permissionKey(),
                    app(UserAccountEntity::class)->permissionKey(),
                    app(RoleEntity::class)->permissionKey(),
                    app(LoginHistoryEntity::class)->permissionKey(),
                ])
        );

        Orbit::registerMenuElement(
            Menu::make(__('Records'))
                ->slug(self::ACCESS_CONTROL_RECORDS_GROUP)
                ->sort(1200)
                ->set('section', __('Users & Roles'))
                ->set('sectionKey', 'access-control')
                ->set('permission', [
                    app(ActivityEntity::class)->permissionKey(),
                    app(VisitorRecordEntity::class)->permissionKey(),
                ])
        );
    }
}
