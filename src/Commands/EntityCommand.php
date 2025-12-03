<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Commands;

use CmsOrbit\Core\Support\PackagePathResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Entity Command
 * 
 * DynamicModel 기반의 엔티티 생성
 * --package 옵션으로 외부 패키지에 생성 가능
 */
class EntityCommand extends Command
{
    /**
     * The name and signature of the console command
     *
     * @var string
     */
    protected $signature = 'cms:entity {name : The name of the entity}
                            {--package= : The package name (vendor/package)}
                            {--m|migration : Create a migration file}';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Create a new DynamicModel entity';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $entityName = $this->argument('name');
        $package = $this->option('package');

        try {
            $this->info("Creating entity: {$entityName}");
            
            if ($package) {
                $this->info("Package: {$package}");
            }

            // Ask for entity type
            $useResource = $this->choice(
                'What type of entity do you want to create?',
                ['Screen-based (Separate screens and layouts)', 'Resource-based (Simple CRUD in one class)'],
                0
            ) === 'Resource-based (Simple CRUD in one class)';

            // Ask about SoftDeletes
            $useSoftDeletes = $this->confirm('Do you want to use SoftDeletes?', true);

            // Create model
            $this->createModel($entityName, $package, $useSoftDeletes);

            // Create factory
            $this->createFactory($entityName, $package);

            if ($useResource) {
                // Create resource
                $this->createResource($entityName, $package, $useSoftDeletes);
            } else {
                // Create screens
                $this->createScreen($entityName, $package, 'list');
                $this->createScreen($entityName, $package, 'edit');
                
                if ($useSoftDeletes) {
                    $this->createScreen($entityName, $package, 'trash');
                }

                // Create layouts
                $this->createLayout($entityName, $package, 'list');
                $this->createLayout($entityName, $package, 'edit');
            }

            // Create routes
            $this->createRoutes($entityName, $package, $useResource, $useSoftDeletes);

            // Create migration if requested or ask
            $shouldCreateMigration = $this->option('migration');
            
            if (!$shouldCreateMigration) {
                $shouldCreateMigration = $this->confirm('Do you want to create a migration?', true);
            }
            
            if ($shouldCreateMigration) {
                $this->createMigration($entityName, $package);
            }

            $this->newLine();
            $this->info('✓ Entity created successfully!');
            $this->newLine();
            $this->comment('Next steps:');
            
            if ($shouldCreateMigration) {
                $this->line('  php artisan migrate');
                $this->line('  php artisan cms:admin-fresh');
            } else {
                $this->line('  php artisan cms:migration create_' . Str::snake(Str::plural($entityName)) . '_table --entity=' . $entityName . ' --type=dynamic --create=' . Str::snake(Str::plural($entityName)));
                $this->line('  php artisan migrate');
                $this->line('  php artisan cms:admin-fresh');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create entity: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Create model file
     */
    protected function createModel(string $entityName, ?string $package, bool $useSoftDeletes): void
    {
        $modelPath = PackagePathResolver::modelPath($package, $entityName, $entityName);
        $modelNamespace = PackagePathResolver::modelNamespace($package, $entityName);

        if (PackagePathResolver::fileExists($modelPath)) {
            if (!$this->confirm("Model already exists. Overwrite?")) {
                return;
            }
        }

        PackagePathResolver::ensureDirectoryExists(dirname($modelPath));

        $factoryNamespace = PackagePathResolver::factoryNamespace($package, $entityName);

        $stub = $this->getStub('entity/model.stub');
        
        // Add SoftDeletes import and use if needed
        if ($useSoftDeletes) {
            $stub = str_replace(
                'use Illuminate\Database\Eloquent\Factories\Factory;',
                'use Illuminate\Database\Eloquent\Factories\Factory;' . "\n" . 'use Illuminate\Database\Eloquent\SoftDeletes;',
                $stub
            );
            $stub = str_replace(
                'class {{ class }} extends DynamicModel' . "\n" . '{' . "\n" . '    use HasPermissions;',
                'class {{ class }} extends DynamicModel' . "\n" . '{' . "\n" . '    use HasPermissions, SoftDeletes;',
                $stub
            );
        }
        
        $content = $this->replaceStubVariables($stub, [
            'namespace' => $modelNamespace,
            'factoryNamespace' => $factoryNamespace,
            'class' => $entityName,
            'table' => Str::snake(Str::plural($entityName)),
        ]);

        file_put_contents($modelPath, $content);

        $this->line("  ✓ Model: {$modelPath}");
    }

    /**
     * Create factory file
     */
    protected function createFactory(string $entityName, ?string $package): void
    {
        $factoryName = $entityName . 'Factory';
        $factoryPath = PackagePathResolver::factoryPath($package, $entityName, $factoryName);
        $factoryNamespace = PackagePathResolver::factoryNamespace($package, $entityName);
        $modelNamespace = PackagePathResolver::modelNamespace($package, $entityName);

        if (PackagePathResolver::fileExists($factoryPath)) {
            return; // Skip if exists
        }

        PackagePathResolver::ensureDirectoryExists(dirname($factoryPath));

        $stub = $this->getStub('entity/factory.stub');
        $content = $this->replaceStubVariables($stub, [
            'namespace' => $factoryNamespace,
            'modelNamespace' => $modelNamespace,
            'class' => $entityName,
        ]);

        file_put_contents($factoryPath, $content);

        $this->line("  ✓ Factory: {$factoryPath}");
    }

    /**
     * Create screen file
     */
    protected function createScreen(string $entityName, ?string $package, string $type): void
    {
        $screenName = $entityName . ucfirst($type) . 'Screen';
        $screenPath = PackagePathResolver::screenPath($package, $entityName, $screenName);
        $screenNamespace = PackagePathResolver::screenNamespace($package, $entityName);
        $modelNamespace = PackagePathResolver::modelNamespace($package, $entityName);
        $layoutNamespace = PackagePathResolver::layoutNamespace($package, $entityName);

        if (PackagePathResolver::fileExists($screenPath)) {
            return; // Skip if exists
        }

        PackagePathResolver::ensureDirectoryExists(dirname($screenPath));

        $stub = $this->getStub("entity/screen.{$type}.stub");
        $content = $this->replaceStubVariables($stub, [
            'namespace' => $screenNamespace,
            'modelNamespace' => $modelNamespace,
            'layoutNamespace' => $layoutNamespace,
            'class' => $entityName,
            'variable' => Str::camel($entityName),
            'variablePlural' => Str::camel(Str::plural($entityName)),
            'routeName' => Str::snake(Str::plural($entityName)),
        ]);

        file_put_contents($screenPath, $content);

        $this->line("  ✓ Screen: {$screenPath}");
    }

    /**
     * Create layout file
     */
    protected function createLayout(string $entityName, ?string $package, string $type): void
    {
        $layoutName = $entityName . ucfirst($type) . 'Layout';
        $layoutPath = PackagePathResolver::layoutPath($package, $entityName, $layoutName);
        $layoutNamespace = PackagePathResolver::layoutNamespace($package, $entityName);
        $modelNamespace = PackagePathResolver::modelNamespace($package, $entityName);

        if (PackagePathResolver::fileExists($layoutPath)) {
            return; // Skip if exists
        }

        PackagePathResolver::ensureDirectoryExists(dirname($layoutPath));

        $stub = $this->getStub("entity/layout.{$type}.stub");
        $content = $this->replaceStubVariables($stub, [
            'namespace' => $layoutNamespace,
            'modelNamespace' => $modelNamespace,
            'class' => $entityName,
            'variable' => Str::camel($entityName),
            'variablePlural' => Str::camel(Str::plural($entityName)),
        ]);

        file_put_contents($layoutPath, $content);

        $this->line("  ✓ Layout: {$layoutPath}");
    }

    /**
     * Create resource file
     */
    protected function createResource(string $entityName, ?string $package, bool $useSoftDeletes): void
    {
        $resourceName = $entityName . 'Resource';
        $resourcePath = PackagePathResolver::entityPath($package, $entityName) . '/' . $resourceName . '.php';
        $resourceNamespace = PackagePathResolver::entityNamespace($package, $entityName);
        $modelNamespace = PackagePathResolver::modelNamespace($package, $entityName);

        if (PackagePathResolver::fileExists($resourcePath)) {
            return; // Skip if exists
        }

        PackagePathResolver::ensureDirectoryExists(dirname($resourcePath));

        $stub = $this->getStub('entity/resource.stub');
        $content = $this->replaceStubVariables($stub, [
            'namespace' => $resourceNamespace,
            'modelNamespace' => $modelNamespace,
            'class' => $entityName,
            'routeName' => Str::snake(Str::plural($entityName)),
        ]);

        file_put_contents($resourcePath, $content);

        $this->line("  ✓ Resource: {$resourcePath}");
    }

    /**
     * Create routes file
     */
    protected function createRoutes(string $entityName, ?string $package, bool $useResource, bool $useSoftDeletes): void
    {
        $routesPath = PackagePathResolver::routesPath($package, $entityName);

        if (PackagePathResolver::fileExists($routesPath)) {
            return; // Skip if exists
        }

        PackagePathResolver::ensureDirectoryExists(dirname($routesPath));

        if ($useResource) {
            // Resource-based routes
            $this->createResourceRoutes($entityName, $package, $useSoftDeletes);
            return;
        }

        $screenNamespace = PackagePathResolver::screenNamespace($package, $entityName);

        $stub = $this->getStub('entity/route.stub');
        
        $routeName = Str::snake(Str::plural($entityName));
        
        $content = $this->replaceStubVariables($stub, [
            'screenNamespace' => $screenNamespace,
            'class' => $entityName,
            'variable' => Str::camel($entityName),
            'routeName' => $routeName,
        ]);
        
        // Add trash routes if SoftDeletes is enabled
        if ($useSoftDeletes) {
            $trashRoute = "\n// Trash routes\n" .
                "Route::screen('entities/{$routeName}/trash', {$screenNamespace}\\{$entityName}TrashScreen::class)\n" .
                "    ->name('orbit.entities.{$routeName}.trash');\n";
            
            $content = rtrim($content) . "\n" . $trashRoute;
        }

        file_put_contents($routesPath, $content);

        $this->line("  ✓ Routes: {$routesPath}");
    }

    /**
     * Create resource routes
     */
    protected function createResourceRoutes(string $entityName, ?string $package, bool $useSoftDeletes): void
    {
        $routesPath = PackagePathResolver::routesPath($package, $entityName);
        $resourceNamespace = PackagePathResolver::entityNamespace($package, $entityName);

        $stub = $this->getStub('entity/route.resource.stub');
        $content = $this->replaceStubVariables($stub, [
            'resourceNamespace' => $resourceNamespace,
            'class' => $entityName,
            'routeName' => Str::snake(Str::plural($entityName)),
        ]);

        file_put_contents($routesPath, $content);

        $this->line("  ✓ Routes: {$routesPath}");
    }

    /**
     * Create migration file
     */
    protected function createMigration(string $entityName, ?string $package): void
    {
        $table = Str::snake(Str::plural($entityName));
        $migrationName = 'create_' . $table . '_table';

        $this->call('cms:migration', [
            'name' => $migrationName,
            '--entity' => $entityName,
            '--package' => $package,
            '--type' => 'dynamic',
            '--create' => $table,
        ]);
    }

    /**
     * Get stub content
     */
    protected function getStub(string $stub): string
    {
        // Try project root stubs first
        $customPath = base_path("stubs/{$stub}");
        if (file_exists($customPath)) {
            return file_get_contents($customPath);
        }

        // Use package stubs
        $packagePath = __DIR__ . "/../../stubs/{$stub}";
        if (file_exists($packagePath)) {
            return file_get_contents($packagePath);
        }

        throw new \RuntimeException("Stub not found: {$stub}");
    }

    /**
     * Replace stub variables
     */
    protected function replaceStubVariables(string $stub, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $stub = str_replace("{{ {$key} }}", $value, $stub);
        }

        return $stub;
    }
}

