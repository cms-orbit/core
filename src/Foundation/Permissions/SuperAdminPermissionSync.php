<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Permissions;

use CmsOrbit\Core\Foundation\Models\Role;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Keeps the system super-admin role's permission map aligned with the live
 * Orbit permission registry (entities, config groups, platform, packages).
 */
class SuperAdminPermissionSync
{
    public const CACHE_KEY = 'orbit:super-admin-permission-fingerprint';

    public const LOCK_KEY = 'orbit:super-admin-sync';

    /**
     * Synchronize the super-admin role when the registered permission set changes.
     *
     * @return bool True when the role was created or updated.
     */
    public function sync(string $name = Role::DisplayNameSuperAdmin, bool $force = false): bool
    {
        if (! $force && ! (bool) config('orbit.permissions.auto_sync_super_admin', true)) {
            return false;
        }

        if (! $this->rolesTableExists()) {
            return false;
        }

        $permissions = Orbit::getAllowAllPermission();
        $fingerprint = $this->fingerprint($permissions->toArray());

        if (! $force && Cache::get(self::CACHE_KEY) === $fingerprint) {
            return false;
        }

        $lock = Cache::lock(self::LOCK_KEY, 10);

        try {
            return $lock->block(5, function () use ($name, $permissions, $fingerprint, $force): bool {
                if (! $force && Cache::get(self::CACHE_KEY) === $fingerprint) {
                    return false;
                }

                $roleModelClass = Orbit::modelClass(Role::class);

                $role = $roleModelClass::query()
                    ->firstOrNew([
                        'system_key' => Role::SystemKeySuperAdmin,
                    ]);

                $role->forceFill([
                    'name' => $name,
                    'slug' => Role::SystemKeySuperAdmin,
                    'system_key' => Role::SystemKeySuperAdmin,
                    'is_deletable' => false,
                    'permissions' => $permissions->toArray(),
                ])->save();

                Cache::forever(self::CACHE_KEY, $fingerprint);

                return true;
            });
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, bool>  $permissions
     */
    public function fingerprint(array $permissions): string
    {
        $slugs = array_keys($permissions);
        sort($slugs);

        return hash('sha256', implode("\n", $slugs));
    }

    protected function rolesTableExists(): bool
    {
        try {
            return Schema::hasTable((new Role)->getTable());
        } catch (Throwable) {
            return false;
        }
    }
}
