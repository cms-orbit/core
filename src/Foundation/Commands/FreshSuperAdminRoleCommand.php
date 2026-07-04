<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands;

use CmsOrbit\Core\Foundation\Models\Role;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Create or refresh a "super-admin" role holding every registered permission
 * (including those submitted by entities and config groups).
 */
#[AsCommand(name: 'orbit:fresh-super-admin-role')]
class FreshSuperAdminRoleCommand extends Command
{
    protected $signature = 'orbit:fresh-super-admin-role {name=최고 관리자}';

    protected $description = 'Create or refresh a role granting every registered permission';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $roleModelClass = Orbit::modelClass(Role::class);

        $role = $roleModelClass::query()
            ->firstOrNew([
                'system_key' => Role::SystemKeySuperAdmin,
            ]);

        $role->forceFill([
            'name'         => $name,
            'slug'         => Role::SystemKeySuperAdmin,
            'system_key'   => Role::SystemKeySuperAdmin,
            'is_deletable' => false,
            'permissions'  => Orbit::getAllowAllPermission()->toArray(),
        ])->save();

        $this->info(sprintf('Role "%s" now grants %d permission(s).', $role->name, count($role->permissions ?? [])));

        return self::SUCCESS;
    }
}
