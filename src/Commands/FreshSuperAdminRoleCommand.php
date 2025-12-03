<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Commands;

use CmsOrbit\Core\Auth\Models\Role;
use CmsOrbit\Core\Models\Concerns\HasPermissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;

/**
 * Fresh Super Admin Role Command
 * 
 * 모든 엔티티의 권한을 수집하여 슈퍼 관리자 역할에 부여
 */
class FreshSuperAdminRoleCommand extends Command
{
    /**
     * The name and signature of the console command
     *
     * @var string
     */
    protected $signature = 'cms:admin-fresh';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Refresh super admin role with all entity permissions';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $this->info('Collecting entity permissions...');

        $permissions = $this->collectPermissions();

        if (empty($permissions)) {
            $this->warn('No permissions found.');
            return self::SUCCESS;
        }

        $this->info('Found ' . count($permissions) . ' permissions');

        // Find or create super admin role
        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Administrator']
        );

        // Update permissions
        $permissionSlugs = array_map(fn($p) => $p->slug, $permissions);
        $superAdminRole->permissions = array_combine($permissionSlugs, $permissionSlugs);
        $superAdminRole->save();

        $this->info('✓ Super admin role updated successfully!');

        return self::SUCCESS;
    }

    /**
     * Collect all permissions from entities
     *
     * @return array
     */
    protected function collectPermissions(): array
    {
        $permissions = [];

        // Scan app/Orbit/Entities directory
        $entitiesPath = app_path('Orbit/Entities');
        
        if (!File::exists($entitiesPath)) {
            return $permissions;
        }

        $entities = File::directories($entitiesPath);

        foreach ($entities as $entityPath) {
            $entityName = basename($entityPath);
            $modelFile = "{$entityPath}/{$entityName}.php";

            if (!File::exists($modelFile)) {
                continue;
            }

            // Try to load the model class
            $namespace = app()->getNamespace() . "Orbit\\Entities\\{$entityName}\\{$entityName}";

            if (!class_exists($namespace)) {
                continue;
            }

            // Check if model uses HasPermissions trait
            $reflection = new ReflectionClass($namespace);
            $traits = $this->getTraitsRecursive($reflection);

            if (!in_array(HasPermissions::class, $traits)) {
                continue;
            }

            // Get permissions from model
            try {
                $modelPermissions = $namespace::getPermissions();
                $permissions = array_merge($permissions, $modelPermissions);
                
                $this->line("  ✓ {$entityName}: " . count($modelPermissions) . " permissions");
            } catch (\Exception $e) {
                $this->warn("  ✗ {$entityName}: {$e->getMessage()}");
            }
        }

        return $permissions;
    }

    /**
     * Get all traits used by a class recursively
     *
     * @param ReflectionClass $class
     * @return array
     */
    protected function getTraitsRecursive(ReflectionClass $class): array
    {
        $traits = [];

        foreach ($class->getTraits() as $trait) {
            $traits[] = $trait->getName();
            $traits = array_merge($traits, $this->getTraitsRecursive($trait));
        }

        if ($parent = $class->getParentClass()) {
            $traits = array_merge($traits, $this->getTraitsRecursive($parent));
        }

        return array_unique($traits);
    }
}

