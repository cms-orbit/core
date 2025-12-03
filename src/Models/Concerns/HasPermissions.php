<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Models\Concerns;

use CmsOrbit\Core\Auth\Permission;
use Illuminate\Support\Str;

/**
 * Has Permissions Trait
 * 
 * 엔티티 모델에 자동 권한 생성 기능 제공
 */
trait HasPermissions
{
    /**
     * Get default permissions for this entity
     *
     * @return array<Permission>
     */
    public static function getDefaultPermissions(): array
    {
        $baseName = Str::snake(class_basename(self::class));
        $pluralName = Str::plural($baseName);
        $studlyName = Str::studly($baseName);

        $permissions = [
            new Permission(
                'orbit.entities.' . $pluralName,
                __('List'),
                __($studlyName)
            ),
            new Permission(
                'orbit.entities.' . $pluralName . '.create',
                __('Create'),
                __($studlyName)
            ),
            new Permission(
                'orbit.entities.' . $pluralName . '.edit',
                __('Edit'),
                __($studlyName)
            ),
            new Permission(
                'orbit.entities.' . $pluralName . '.delete',
                __('Delete'),
                __($studlyName)
            ),
        ];

        // Add soft delete permissions if the model uses SoftDeletes
        if (in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive(static::class)
        )) {
            $permissions[] = new Permission(
                'orbit.entities.' . $pluralName . '.trash',
                __('Trash'),
                __($studlyName)
            );
            $permissions[] = new Permission(
                'orbit.entities.' . $pluralName . '.destroy',
                __('Destroy'),
                __($studlyName)
            );
            $permissions[] = new Permission(
                'orbit.entities.' . $pluralName . '.restore',
                __('Restore'),
                __($studlyName)
            );
        }

        return $permissions;
    }

    /**
     * Get permissions for this entity
     * Override this method to customize permissions
     *
     * @return array<Permission>
     */
    public static function getPermissions(): array
    {
        return self::getDefaultPermissions();
    }
}

