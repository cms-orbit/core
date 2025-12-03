<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Commands;

use CmsOrbit\Core\Support\PackagePathResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Document Command
 * 
 * DocumentModel 기반의 문서 엔티티 생성
 * --package 옵션으로 외부 패키지에 생성 가능
 */
class DocumentCommand extends Command
{
    /**
     * The name and signature of the console command
     *
     * @var string
     */
    protected $signature = 'cms:document {name : The name of the document}
                            {--package= : The package name (vendor/package)}
                            {--m|migration : Create a migration file}';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Create a new DocumentModel document';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $documentName = $this->argument('name');
        $package = $this->option('package');

        try {
            $this->info("Creating document: {$documentName}");
            
            if ($package) {
                $this->info("Package: {$package}");
            }

            // Create model
            $this->createModel($documentName, $package);

            // Create factory
            $this->createFactory($documentName, $package);

            // Create presenter
            $this->createPresenter($documentName, $package);

            // Create screens
            $this->createScreen($documentName, $package, 'list');
            $this->createScreen($documentName, $package, 'edit');

            // Create layouts
            $this->createLayout($documentName, $package, 'list');
            $this->createLayout($documentName, $package, 'edit');

            // Create routes
            $this->createRoutes($documentName, $package);

            // Create migration if requested or ask
            $shouldCreateMigration = $this->option('migration');
            
            if (!$shouldCreateMigration) {
                $shouldCreateMigration = $this->confirm('Do you want to create a migration?', true);
            }
            
            if ($shouldCreateMigration) {
                $this->createMigration($documentName, $package);
            }

            $this->newLine();
            $this->info('✓ Document created successfully!');
            $this->newLine();
            $this->comment('Next steps:');
            
            if ($shouldCreateMigration) {
                $this->line('  php artisan migrate');
                $this->line('  php artisan cms:admin-fresh');
            } else {
                $this->line('  php artisan cms:migration create_' . Str::snake(Str::plural($documentName)) . '_table --entity=' . $documentName . ' --type=document --create=' . Str::snake(Str::plural($documentName)));
                $this->line('  php artisan migrate');
                $this->line('  php artisan cms:admin-fresh');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to create document: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Create model file
     */
    protected function createModel(string $documentName, ?string $package): void
    {
        $modelPath = PackagePathResolver::modelPath($package, $documentName, $documentName);
        $modelNamespace = PackagePathResolver::modelNamespace($package, $documentName);
        $presenterNamespace = PackagePathResolver::presenterNamespace($package, $documentName);

        if (PackagePathResolver::fileExists($modelPath)) {
            if (!$this->confirm("Model already exists. Overwrite?")) {
                return;
            }
        }

        PackagePathResolver::ensureDirectoryExists(dirname($modelPath));

        $factoryNamespace = PackagePathResolver::factoryNamespace($package, $documentName);

        $stub = $this->getStub('document/model.stub');
        $content = $this->replaceStubVariables($stub, [
            'namespace' => $modelNamespace,
            'presenterNamespace' => $presenterNamespace,
            'factoryNamespace' => $factoryNamespace,
            'class' => $documentName,
            'table' => Str::snake(Str::plural($documentName)),
            'entityNamespace' => $modelNamespace,
        ]);

        file_put_contents($modelPath, $content);

        $this->line("  ✓ Model: {$modelPath}");
    }

    /**
     * Create factory file
     */
    protected function createFactory(string $documentName, ?string $package): void
    {
        $factoryName = $documentName . 'Factory';
        $factoryPath = PackagePathResolver::factoryPath($package, $documentName, $factoryName);
        $factoryNamespace = PackagePathResolver::factoryNamespace($package, $documentName);
        $modelNamespace = PackagePathResolver::modelNamespace($package, $documentName);

        if (PackagePathResolver::fileExists($factoryPath)) {
            return; // Skip if exists
        }

        PackagePathResolver::ensureDirectoryExists(dirname($factoryPath));

        $stub = $this->getStub('document/factory.stub');
        $content = $this->replaceStubVariables($stub, [
            'namespace' => $factoryNamespace,
            'modelNamespace' => $modelNamespace,
            'class' => $documentName,
        ]);

        file_put_contents($factoryPath, $content);

        $this->line("  ✓ Factory: {$factoryPath}");
    }

    /**
     * Create presenter file
     */
    protected function createPresenter(string $documentName, ?string $package): void
    {
        $presenterName = $documentName . 'Presenter';
        $presenterPath = PackagePathResolver::presenterPath($package, $documentName, $presenterName);
        $presenterNamespace = PackagePathResolver::presenterNamespace($package, $documentName);
        $modelNamespace = PackagePathResolver::modelNamespace($package, $documentName);

        if (PackagePathResolver::fileExists($presenterPath)) {
            return; // Skip if exists
        }

        PackagePathResolver::ensureDirectoryExists(dirname($presenterPath));

        $stub = $this->getStub('document/presenter.stub');
        $content = $this->replaceStubVariables($stub, [
            'namespace' => $presenterNamespace,
            'modelNamespace' => $modelNamespace,
            'class' => $documentName,
        ]);

        file_put_contents($presenterPath, $content);

        $this->line("  ✓ Presenter: {$presenterPath}");
    }

    /**
     * Create screen file
     */
    protected function createScreen(string $documentName, ?string $package, string $type): void
    {
        $screenName = $documentName . ucfirst($type) . 'Screen';
        $screenPath = PackagePathResolver::screenPath($package, $documentName, $screenName);
        $screenNamespace = PackagePathResolver::screenNamespace($package, $documentName);
        $modelNamespace = PackagePathResolver::modelNamespace($package, $documentName);
        $layoutNamespace = PackagePathResolver::layoutNamespace($package, $documentName);

        if (PackagePathResolver::fileExists($screenPath)) {
            return; // Skip if exists
        }

        PackagePathResolver::ensureDirectoryExists(dirname($screenPath));

        $stub = $this->getStub("document/screen.{$type}.stub");
        $content = $this->replaceStubVariables($stub, [
            'namespace' => $screenNamespace,
            'modelNamespace' => $modelNamespace,
            'layoutNamespace' => $layoutNamespace,
            'class' => $documentName,
            'variable' => Str::camel($documentName),
            'variablePlural' => Str::camel(Str::plural($documentName)),
            'routeName' => Str::snake(Str::plural($documentName)),
        ]);

        file_put_contents($screenPath, $content);

        $this->line("  ✓ Screen: {$screenPath}");
    }

    /**
     * Create layout file
     */
    protected function createLayout(string $documentName, ?string $package, string $type): void
    {
        $layoutName = $documentName . ucfirst($type) . 'Layout';
        $layoutPath = PackagePathResolver::layoutPath($package, $documentName, $layoutName);
        $layoutNamespace = PackagePathResolver::layoutNamespace($package, $documentName);
        $modelNamespace = PackagePathResolver::modelNamespace($package, $documentName);

        if (PackagePathResolver::fileExists($layoutPath)) {
            return; // Skip if exists
        }

        PackagePathResolver::ensureDirectoryExists(dirname($layoutPath));

        $stub = $this->getStub("document/layout.{$type}.stub");
        $content = $this->replaceStubVariables($stub, [
            'namespace' => $layoutNamespace,
            'modelNamespace' => $modelNamespace,
            'class' => $documentName,
            'variable' => Str::camel($documentName),
            'variablePlural' => Str::camel(Str::plural($documentName)),
        ]);

        file_put_contents($layoutPath, $content);

        $this->line("  ✓ Layout: {$layoutPath}");
    }

    /**
     * Create routes file
     */
    protected function createRoutes(string $documentName, ?string $package): void
    {
        $routesPath = PackagePathResolver::routesPath($package, $documentName);
        $screenNamespace = PackagePathResolver::screenNamespace($package, $documentName);

        if (PackagePathResolver::fileExists($routesPath)) {
            return; // Skip if exists
        }

        PackagePathResolver::ensureDirectoryExists(dirname($routesPath));

        $stub = $this->getStub('document/route.stub');
        $content = $this->replaceStubVariables($stub, [
            'screenNamespace' => $screenNamespace,
            'class' => $documentName,
            'variable' => Str::camel($documentName),
            'routeName' => Str::snake(Str::plural($documentName)),
        ]);

        file_put_contents($routesPath, $content);

        $this->line("  ✓ Routes: {$routesPath}");
    }

    /**
     * Create migration file
     */
    protected function createMigration(string $documentName, ?string $package): void
    {
        $table = Str::snake(Str::plural($documentName));
        $migrationName = 'create_' . $table . '_table';

        $this->call('cms:migration', [
            'name' => $migrationName,
            '--entity' => $documentName,
            '--package' => $package,
            '--type' => 'document',
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

