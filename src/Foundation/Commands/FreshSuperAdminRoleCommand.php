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
    protected $signature = 'orbit:fresh-super-admin-role {name=super-admin}';

    protected $description = 'Create or refresh a role granting every registered permission';

    public function handle(): int
    {
        $name = (string) $this->argument('name');

        $permissions = Orbit::getAllowAllPermission()->toArray();

        $role = Orbit::modelClass(Role::class)::updateOrCreate(
            ['name' => $name],
            ['permissions' => $permissions]
        );

        $this->info(sprintf('Role "%s" now grants %d permission(s).', $role->name, count($permissions)));

        return self::SUCCESS;
    }
}
