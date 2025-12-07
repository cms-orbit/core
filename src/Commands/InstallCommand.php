<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cms:install')]
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command
     *
     * @var string
     */
    protected $signature = 'cms:install
                            {--force : Force the operation to run}
                            {--entities : Enhance User model with Orbit features}
                            {--remove-models : Remove app/Models directory}
                            {--skip-migrations : Skip running migrations}';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Install CMS Orbit';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $this->info('🚀 Installing CMS Orbit...');
        $this->newLine();

        // 1. Publish config
        $this->publishConfig();

        // 2. Publish migrations
        $this->publishMigrations();

        // 3. Publish entities (required) & 4. Remove app/Models (required, warning)
        $this->warn('⚠️  This step will overwrite base entities (User, Role) and remove the entire app/Models directory. This action is REQUIRED for Orbit installation and cannot be undone!');
        $confirmation = $this->ask('To continue, please type "install"');

        if (trim(strtolower($confirmation)) !== 'install') {
            $this->error('Installation aborted. You must type "install" to continue.');
            return self::FAILURE;
        }

        $this->publishEntities();
        $this->files->deleteDirectory(app_path('Models'));
        // 프로젝트 루트 내의 모든 PHP 파일(단, vendor 제외)에서 App\Entities\User\User를 새로 배포된 User 엔티티(예: App\Entities\User)로 치환합니다.
        $this->info('🔍 Replacing App\Entities\User\User references with new User entity...');

        $rootPath = base_path();
        $searchString = 'App\Models\User';
        $replaceString = 'App\Entities\User\User';

        // Use Laravel's file system for recursion instead to avoid PHP's RecursiveDirectoryIterator
        $allPhpFiles = collect($this->files->allFiles($rootPath))
            ->filter(function ($file) {
                /** @var \Symfony\Component\Finder\SplFileInfo $file */
                // Exclude anything in "vendor" directory
                return strpos($file->getRealPath(), DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) === false
                    && $file->getExtension() === 'php';
            });

        foreach ($allPhpFiles as $file) {
            $filePath = $file->getRealPath();
            $contents = $this->files->get($filePath);

            if (strpos($contents, $searchString) !== false) {
                $newContents = str_replace($searchString, $replaceString, $contents);
                $this->files->put($filePath, $newContents);
                $this->line("  ✓ Patched: " . str_replace($rootPath . DIRECTORY_SEPARATOR, '', $filePath));
            }
        }

        $this->info('✅ All references to App\Entities\User\User have been updated to App\Entities\User\User.');

        // 5. Run migrations
        if (!$this->option('skip-migrations')) {
            if ($this->confirm('Run migrations now?', true)) {
                $this->runMigrations();
            }
        }

        // 6. Fresh admin role
        $this->freshAdminRole();
        // 8. Setup AppServiceProvider
        $this->setupAppServiceProvider();

        $this->newLine();
        $this->info('✅ CMS Orbit installed successfully!');
        $this->newLine();
        $this->comment('Next steps:');
        $this->line('  1. You must run: composer dump-autoload');
        $this->line('     (The User model has been deleted and newly published entities will not be available until you refresh autoloads.)');
        $this->line('  2. Start creating entities: php artisan cms:entity Product -m');

        return self::SUCCESS;
    }

    /**
     * Publish config files
     */
    protected function publishConfig(): void
    {
        $this->info('📝 Publishing configuration...');

        Artisan::call('vendor:publish', [
            '--tag' => 'orbit-config',
            '--force' => $this->option('force'),
        ]);

        $this->line('  ✓ Config published');
    }

    /**
     * Publish migrations
     */
    protected function publishMigrations(): void
    {
        $this->info('📦 Publishing migrations...');

        Artisan::call('vendor:publish', [
            '--tag' => 'orbit-migrations',
            '--force' => $this->option('force'),
        ]);

        $this->line('  ✓ Migrations published');
    }

    /**
     * Publish base entities
     */
    protected function publishEntities(): void
    {
        $this->info('🎯 Enhancing User model with Orbit features...');

        $sourceDir = base_path('vendor/cms-orbit/core/src/Entities');
        $targetDir = app_path('Entities');

        if (!is_dir($sourceDir)) {
            $this->warn('  ⚠ Entities directory not found in vendor package.');
            return;
        }

        // If Entities directory already exists in app, backup
        if (is_dir($targetDir)) {
            $this->files->deleteDirectory($targetDir);
        }

        // Recursively copy each file, updating namespaces
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS));

        foreach ($rii as $file) {
            if ($file->isDir()) continue;

            $srcPath = $file->getPathname();
            $subPath = ltrim(str_replace($sourceDir, '', $srcPath), DIRECTORY_SEPARATOR);
            $destPath = $targetDir . DIRECTORY_SEPARATOR . $subPath;

            // Ensure destination directory exists
            if (!is_dir(dirname($destPath))) {
                mkdir(dirname($destPath), 0755, true);
            }

            $contents = file_get_contents($srcPath);

            // Replace namespace from 'CmsOrbit\Core\Entities' to 'App\Entities'
            $contents = preg_replace_callback(
                '/namespace\s+([^\s;]+);/i',
                function ($matches) {
                    $original = $matches[1];

                    // Replace leading CmsOrbit\Core\Entities with App\Entities
                    $replaced = preg_replace('/^CmsOrbit\\\\Core\\\\Entities/', 'App\Entities', $original);

                    return 'namespace ' . $replaced . ';';
                },
                $contents
            );

            // Also replace usages: use CmsOrbit\Core\Entities... => use App\Entities...
            $contents = preg_replace(
                '/use\s+CmsOrbit\\\\Core\\\\Entities(.*);/i',
                'use App\Entities$1;',
                $contents
            );


            file_put_contents($destPath, $contents);
        }

        $this->line('  ✓ Entities copied to app/Entities with updated namespaces');
    }

    /**
     * Run migrations
     */
    protected function runMigrations(): void
    {
        $this->info('🗄️  Running migrations...');

        Artisan::call('migrate', [], $this->output);

        $this->line('  ✓ Migrations completed');
    }

    /**
     * Fresh admin role
     */
    protected function freshAdminRole(): void
    {
        $this->info('🔐 Setting up permissions...');

        Artisan::call('cms:admin-fresh', [], $this->output);

        $this->line('  ✓ Admin role created');
    }

    /**
     * Create admin user
     */
    protected function createAdminUser(): void
    {
        $this->info('👤 Creating admin user...');
        $this->newLine();

        $name = $this->ask('Admin name', 'Admin');
        $email = $this->ask('Admin email', 'admin@admin.com');
        $password = $this->secret('Admin password');

        Artisan::call('cms:admin', [
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], $this->output);
    }

    /**
     * Setup AppServiceProvider
     */
    protected function setupAppServiceProvider(): void
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');

        if (!file_exists($providerPath)) {
            return;
        }

        $content = $this->files->get($providerPath);

        // Check if already configured
        if (str_contains($content, 'EntityBootstrapper')) {
            $this->line('  ℹ AppServiceProvider already configured');
            return;
        }

        if (!$this->confirm('Add entity discovery to AppServiceProvider?', true)) {
            return;
        }

        $this->info('⚙️  Configuring AppServiceProvider...');

        // Add entity discovery code
        $bootMethod = <<<'PHP'
    public function boot(): void
    {
        // Entity Discovery System
        $entities = app(\CmsOrbit\Core\Support\EntityBootstrapper::class);
        $entities->registerPath(app_path('Entities'));
        $entities->bootstrap(menuSort: 1000);
    }
PHP;

        $content = preg_replace(
            '/public function boot\(\): void\s*\{[^}]*\}/',
            $bootMethod,
            $content
        );

        $this->files->put($providerPath, $content);

        $this->line('  ✓ AppServiceProvider configured');
    }
}
