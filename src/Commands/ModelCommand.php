<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Commands;

use CmsOrbit\Core\Support\PackagePathResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Model Command
 *
 * 단순 Eloquent 모델 생성
 * --entity 옵션으로 엔티티 디렉터리 내에 생성
 * --package 옵션으로 외부 패키지에 생성
 */
class ModelCommand extends Command
{
    /**
     * The name and signature of the console command
     *
     * @var string
     */
    protected $signature = 'cms:model {name : The name of the model}
                            {--entity= : The entity name (parent entity)}
                            {--package= : The package name (vendor/package)}
                            {--m|migration : Create a migration file}
                            {--type= : Model type (dynamic, document, or default)}';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent model';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $modelName = $this->argument('name');
        $entityName = $this->option('entity') ?: $modelName;
        $package = $this->option('package');
        $type = $this->option('type');

        try {
            $this->info("Creating model: {$modelName}");

            if ($package) {
                $this->info("Package: {$package}");
            }

            if ($this->option('entity')) {
                $this->info("Entity: {$entityName}");
            }

            // Create model
            $this->createModel($modelName, $entityName, $package, $type);

            // Create migration if requested
            if ($this->option('migration')) {
                $this->createMigration($modelName, $entityName, $package, $type);
            }

            $this->newLine();
            $this->info('✓ Model created successfully!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create model: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Create model file
     */
    protected function createModel(string $modelName, string $entityName, ?string $package, ?string $type): void
    {
        $modelPath = PackagePathResolver::modelPath($package, $entityName, $modelName);
        $modelNamespace = PackagePathResolver::modelNamespace($package, $entityName);

        if (PackagePathResolver::fileExists($modelPath)) {
            if (!$this->confirm("Model already exists. Overwrite?")) {
                return;
            }
        }

        PackagePathResolver::ensureDirectoryExists(dirname($modelPath));

        // Select stub based on type
        $stubPath = match ($type) {
            'dynamic' => 'entity/model.stub',
            'document' => 'document/model.stub',
            default => 'model.stub',
        };

        $stub = $this->getStub($stubPath);

        $variables = [
            'namespace' => $modelNamespace,
            'class' => $modelName,
            'table' => Str::snake(Str::plural($modelName)),
        ];

        // Add presenter namespace for document type
        if ($type === 'document') {
            $variables['presenterNamespace'] = PackagePathResolver::presenterNamespace($package, $entityName);
        }

        $content = $this->replaceStubVariables($stub, $variables);

        file_put_contents($modelPath, $content);

        $this->line("  ✓ Model: {$modelPath}");
    }

    /**
     * Create migration file
     */
    protected function createMigration(string $modelName, string $entityName, ?string $package, ?string $type): void
    {
        $table = Str::snake(Str::plural($modelName));
        $migrationName = 'create_'.$table.'_table';

        $this->call('cms:migration', [
            'name' => $migrationName,
            '--entity' => $entityName,
            '--package' => $package,
            '--type' => $type,
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
        $packagePath = __DIR__."/../../stubs/{$stub}";
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

