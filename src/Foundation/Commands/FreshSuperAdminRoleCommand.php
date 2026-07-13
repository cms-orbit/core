<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands;

use CmsOrbit\Core\Foundation\Models\Role;
use CmsOrbit\Core\Foundation\Permissions\SuperAdminPermissionSync;
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

    public function handle(SuperAdminPermissionSync $sync): int
    {
        $name = (string) $this->argument('name');

        if ($name === '') {
            $name = Role::DisplayNameSuperAdmin;
        }

        $updated = $sync->sync($name, force: true);

        if (! $updated) {
            $this->warn('Unable to refresh the super-admin role (roles table missing or sync failed).');

            return self::FAILURE;
        }

        $role = Role::query()
            ->where('system_key', Role::SystemKeySuperAdmin)
            ->first();

        $this->info(sprintf(
            'Role "%s" now grants %d permission(s).',
            $role?->name ?? $name,
            count($role?->permissions ?? []),
        ));

        return self::SUCCESS;
    }
}
