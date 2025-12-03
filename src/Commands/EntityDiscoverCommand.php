<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Commands;

use CmsOrbit\Core\Support\EntityDiscovery;
use CmsOrbit\Core\Support\PackageManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

/**
 * Entity Discover Command
 * 
 * 엔티티를 자동으로 발견하고 라우트, 마이그레이션, 메뉴를 등록합니다.
 */
class EntityDiscoverCommand extends Command
{
    /**
     * The name and signature of the console command
     *
     * @var string
     */
    protected $signature = 'cms:discover';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Discover and register all entities';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $this->info('Discovering entities...');

        /** @var EntityDiscovery $discovery */
        $discovery = app(EntityDiscovery::class);
        
        /** @var PackageManager $packageManager */
        $packageManager = app(PackageManager::class);

        // Register default entity path
        $discovery->registerPath(app_path('Entities'));

        // Register paths from packages
        foreach ($packageManager->getEntityPaths() as $path) {
            $discovery->registerPath($path);
        }

        // Discover entities
        $entities = $discovery->discover();

        $this->newLine();
        $this->info("Found {$entities->count()} entities:");
        $this->newLine();

        foreach ($entities as $entity) {
            $type = $entity['is_resource'] ? 'Resource' : 'Screen';
            $softDeletes = $entity['has_soft_deletes'] ? '+ SoftDeletes' : '';
            
            $this->line("  ✓ {$entity['name']} ({$type}) {$softDeletes}");
            $this->comment("    Routes: {$entity['routes']}");
            $this->comment("    Migrations: {$entity['migrations']}");
        }

        $this->newLine();
        $this->info('✓ Entity discovery completed!');
        $this->newLine();
        $this->comment('Routes and migrations are automatically loaded.');
        $this->comment('Menus can be registered in your AppServiceProvider.');

        return self::SUCCESS;
    }
}

