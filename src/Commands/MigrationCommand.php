<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Commands;

use CmsOrbit\Core\Support\PackagePathResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Migration Command
 * 
 * 엔티티 마이그레이션 생성
 * --entity 옵션으로 엔티티 디렉터리 내에 생성
 * --package 옵션으로 외부 패키지에 생성
 * --type 옵션으로 dynamic/document 템플릿 사용
 */
class MigrationCommand extends Command
{
    /**
     * The name and signature of the console command
     *
     * @var string
     */
    protected $signature = 'cms:migration {name : The name of the migration}
                            {--entity= : The entity name}
                            {--package= : The package name (vendor/package)}
                            {--type= : Migration type (dynamic, document)}
                            {--create= : The table to be created}
                            {--table= : The table to migrate}';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Create a new migration file in entity directory';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $entityName = $this->option('entity');
        $package = $this->option('package');
        $type = $this->option('type');
        $table = $this->option('create') ?: $this->option('table');

        try {
            $this->info("Creating migration: {$name}");
            
            if ($package) {
                $this->info("Package: {$package}");
            }
            
            if ($entityName) {
                $this->info("Entity: {$entityName}");
            }
            
            if ($type) {
                $this->info("Type: {$type}");
            }

            $this->createMigration($name, $entityName, $package, $type, $table);

            $this->newLine();
            $this->info('✓ Migration created successfully!');
            $this->newLine();
            $this->comment('Next step:');
            $this->line('  php artisan migrate');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create migration: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Create migration file
     */
    protected function createMigration(
        string $name,
        ?string $entityName,
        ?string $package,
        ?string $type,
        ?string $table
    ): void {
        // Determine migration path
        if ($entityName) {
            $entityPath = PackagePathResolver::entityPath($package, $entityName);
            $migrationPath = $entityPath.'/database/migrations';
        } else {
            $migrationPath = database_path('migrations');
        }

        PackagePathResolver::ensureDirectoryExists($migrationPath);

        // Generate migration filename with timestamp
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";
        $fullPath = $migrationPath.'/'.$filename;

        if (file_exists($fullPath)) {
            if (!$this->confirm("Migration already exists. Overwrite?")) {
                return;
            }
        }

        // Get stub based on type and operation
        $isUpdate = $this->option('table') && !$this->option('create');
        
        if ($isUpdate) {
            $stubPath = 'migration/update.stub';
        } else {
            $stubPath = match ($type) {
                'dynamic' => 'migration/dynamic.stub',
                'document' => 'migration/document.stub',
                default => 'migration/default.stub',
            };
        }

        $stub = $this->getStub($stubPath);
        $content = $this->replaceStubVariables($stub, [
            'table' => $table ?: 'table_name',
        ]);

        file_put_contents($fullPath, $content);

        $this->line("  ✓ Migration: {$fullPath}");
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
        $packagePath = __DIR__."/../../stubs/{$stub}";
        if (file_exists($packagePath)) {
            return file_get_contents($packagePath);
        }

        // Fallback to default stub
        $defaultPath = __DIR__."/../../stubs/migration/default.stub";
        if (file_exists($defaultPath)) {
            return file_get_contents($defaultPath);
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

