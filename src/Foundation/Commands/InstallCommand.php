<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Commands;

use App\Models\User;
use CmsOrbit\Core\Boost\BoostPackageSync;
use CmsOrbit\Core\Foundation\Commands\Support\InstallLocale;
use CmsOrbit\Core\Foundation\Commands\Support\InstallMessages;
use CmsOrbit\Core\Foundation\Events\InstallEvent;
use CmsOrbit\Core\Foundation\Providers\ConsoleServiceProvider;
use CmsOrbit\Core\Support\Facades\Orbit;
use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Traits\Conditionable;
use Laravel\Boost\BoostServiceProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\ExecutableFinder;

use function Laravel\Prompts\multiselect;

#[AsCommand(name: 'orbit:install')]
class InstallCommand extends Command
{
    use Conditionable;

    /**
     * Repository opened when the user agrees to star the project.
     */
    public const REPOSITORY_URL = 'https://github.com/cms-orbit/core';

    /**
     * Optional satellite packages that orbit:install can offer.
     *
     * @var array<string, array{package: string, constraint: string, label: string}>
     */
    public const SATELLITE_PACKAGES = [
        'announcement' => [
            'package' => 'cms-orbit/announcement',
            'constraint' => '^4.0',
            'label' => 'Announcement (document announcements)',
        ],
        'popup' => [
            'package' => 'cms-orbit/popup',
            'constraint' => '^4.0',
            'label' => 'Popup (document popups)',
        ],
        'sendgo' => [
            'package' => 'cms-orbit/sendgo',
            'constraint' => '^4.0',
            'label' => 'SendGo (messaging admin GUI)',
        ],
    ];

    /**
     * @var string
     */
    protected $signature = 'orbit:install
                            {--skip-npm : Skip installing and building frontend assets}
                            {--with= : Comma-separated satellite packages (announcement,popup,sendgo)}';

    /**
     * @var string
     */
    protected $description = 'Install all of the Orbit files';

    private InstallMessages $messages;

    /**
     * @return int
     */
    public function handle(): int
    {
        $locale = $this->resolveInstallLocale();
        $this->messages = InstallMessages::for($locale);

        $this->comment($this->messages->get('started'));
        $this->info($this->messages->get('version', ['version' => Orbit::version()]));

        $this
            ->publishPackageAssets()
            ->runMigrations()
            ->linkStorage()
            ->configureUserModel()
            ->when(class_exists(User::class), function () {
                $this->info($this->messages->get('step_namespace_replace'));
                $this->replaceInFiles(app_path(), 'use CmsOrbit\\Core\\Foundation\\Models\\User;', 'use App\\Models\\User;');
            })
            ->prepareEntitiesDirectory()
            ->prepareOrbitProvider()
            ->syncFrontendScaffolding()
            ->buildFrontendAssets()
            ->publishAiSkills()
            ->syncBoostGuidelines()
            ->installOptionalSatellites()
            ->promptForStargazer($locale)
            ->finish();

        event(new InstallEvent($this));

        return self::SUCCESS;
    }

    private function resolveInstallLocale(): InstallLocale
    {
        $default = InstallLocale::defaultFromAppLocale();

        if ($this->shouldSkipPrompts()) {
            return $default;
        }

        $options = array_map(
            static fn (InstallLocale $locale): string => $locale->label(),
            InstallLocale::cases(),
        );

        $selected = $this->choice(
            'Select installation language / 설치 언어를 선택하세요',
            $options,
            $default === InstallLocale::Korean ? 0 : 1,
        );

        return $selected === InstallLocale::Korean->label()
            ? InstallLocale::Korean
            : InstallLocale::English;
    }

    private function shouldSkipPrompts(): bool
    {
        return App::runningUnitTests() || $this->option('no-interaction');
    }

    /**
     * @return $this
     */
    private function publishPackageAssets(): self
    {
        $this->info($this->messages->get('step_publish'));

        return $this->executeCommand('vendor:publish', [
            '--provider' => ConsoleServiceProvider::class,
            '--tag'      => [
                'orbit-config',
                'orbit-migrations',
                'orbit-app-stubs',
                'orbit-assets',
            ],
        ]);
    }

    /**
     * @return $this
     */
    private function runMigrations(): self
    {
        $this->info($this->messages->get('step_migrate'));

        return $this->executeCommand('migrate');
    }

    /**
     * @return $this
     */
    private function linkStorage(): self
    {
        $this->info($this->messages->get('step_storage_link'));

        return $this->executeCommand('storage:link');
    }

    /**
     * @return $this
     */
    private function configureUserModel(string $path = 'Models/User.php'): self
    {
        $this->info($this->messages->get('step_user_model'));

        $absolutePath = app_path($path);

        if (! file_exists($absolutePath)) {
            $this->info($this->messages->get('user_model_creating'));
            $this->writeUserStub($absolutePath);

            return $this;
        }

        if ($this->shouldOverwriteUserModel()) {
            $this->info($this->messages->get('user_model_overwriting'));
            $this->writeUserStub($absolutePath);

            return $this;
        }

        $this->info($this->messages->get('user_model_kept'));
        $this->displayUserModelCompatibilityGuide();

        return $this;
    }

    private function shouldOverwriteUserModel(): bool
    {
        if ($this->shouldSkipPrompts()) {
            return false;
        }

        $this->newLine();
        $this->warn($this->messages->get('user_model_overwrite_warning'));

        return $this->confirm(
            $this->messages->get('user_model_overwrite_confirm'),
            true,
        );
    }

    private function writeUserStub(string $absolutePath): void
    {
        $directory = dirname($absolutePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($absolutePath, file_get_contents(Orbit::path('stubs/app/User.stub')));
    }

    private function displayUserModelCompatibilityGuide(): void
    {
        $this->newLine();
        $this->components->twoColumnDetail(
            $this->messages->get('user_model_compat_title'),
            '',
        );

        foreach ([
            'user_model_compat_intro',
            'user_model_compat_option_extend',
            'user_model_compat_option_traits',
            'user_model_compat_trait_user_access',
            'user_model_compat_trait_accounts',
            'user_model_compat_use_model',
            'user_model_compat_use_model_code',
            'user_model_compat_auth',
            'user_model_compat_casts',
        ] as $key) {
            $this->line($this->messages->get($key));
        }

        $this->newLine();
    }

    /**
     * @return $this
     */
    private function prepareEntitiesDirectory(): self
    {
        $this->info($this->messages->get('step_entities'));

        $path = base_path('entities');

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
            file_put_contents($path.'/.gitkeep', '');
            $this->info($this->messages->get('entities_created'));
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function prepareOrbitProvider(): self
    {
        $this->info($this->messages->get('step_orbit_provider'));

        $path = app_path('Orbit/OrbitProvider.php');

        if (file_exists($path)) {
            return $this;
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, file_get_contents(Orbit::path('stubs/app/OrbitProvider.stub')));

        $this->info($this->messages->get('orbit_provider_created'));

        return $this;
    }

    /**
     * @return $this
     */
    private function syncFrontendScaffolding(): self
    {
        $this->info($this->messages->get('step_frontend_sync'));

        return $this->executeCommand('orbit:frontend-sync');
    }

    /**
     * Install the newly merged NPM dependencies and build the Vite manifest so
     * a plain host can serve the Orbit admin panel immediately after install.
     *
     * @return $this
     */
    private function buildFrontendAssets(): self
    {
        if (App::runningUnitTests() || $this->option('skip-npm')) {
            return $this;
        }

        if (! is_file(base_path('package.json'))) {
            return $this;
        }

        $npm = (new ExecutableFinder)->find('npm');

        if ($npm === null) {
            $this->warn($this->messages->get('frontend_npm_missing'));

            return $this;
        }

        $this->info($this->messages->get('step_frontend_build'));

        $stream = function (string $type, string $buffer): void {
            $this->output->write($buffer);
        };

        $this->line($this->messages->get('frontend_install_running'));
        $install = Process::path(base_path())->timeout(600)->run([$npm, 'install'], $stream);

        if (! $install->successful()) {
            $this->warn($this->messages->get('frontend_build_failed'));

            return $this;
        }

        $this->line($this->messages->get('frontend_build_running'));
        $build = Process::path(base_path())->timeout(600)->run([$npm, 'run', 'build'], $stream);

        if (! $build->successful()) {
            $this->warn($this->messages->get('frontend_build_failed'));

            return $this;
        }

        $this->info($this->messages->get('frontend_build_done'));

        return $this;
    }

    /**
     * @return $this
     */
    private function syncBoostGuidelines(): self
    {
        $sync = app(BoostPackageSync::class);
        $packageCount = $sync->registerOrbitPackages();

        if ($packageCount > 0) {
            $this->line($this->messages->get('boost_packages_registered', ['count' => $packageCount]));
        }

        if (! $sync->canRefresh()) {
            if (class_exists(BoostServiceProvider::class)) {
                $this->comment($this->messages->get('boost_install_hint'));
            }

            return $this;
        }

        $this->info($this->messages->get('step_boost_update'));

        return $this->executeCommand('boost:update');
    }

    /**
     * @return $this
     */
    private function publishAiSkills(): self
    {
        $this->info($this->messages->get('step_ai'));

        return $this->executeCommand('orbit:ai');
    }

    /**
     * Offer or install optional satellite packages (announcement / popup / sendgo).
     *
     * @return $this
     */
    private function installOptionalSatellites(): self
    {
        $keys = $this->resolveSatelliteSelections();

        if ($keys === []) {
            return $this;
        }

        $this->info($this->messages->get('step_satellites'));

        $requireArgs = [];

        foreach ($keys as $key) {
            $meta = self::SATELLITE_PACKAGES[$key];
            $requireArgs[] = $meta['package'].':'.$meta['constraint'];
        }

        $composer = (new ExecutableFinder)->find('composer') ?? 'composer';
        $command = array_merge([$composer, 'require', ...$requireArgs, '--no-interaction']);

        $this->line($this->messages->get('satellites_require_running', [
            'packages' => implode(' ', $requireArgs),
        ]));

        $result = Process::path(base_path())->timeout(600)->run($command, function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if (! $result->successful()) {
            $this->warn($this->messages->get('satellites_require_failed', [
                'command' => implode(' ', $command),
            ]));

            return $this;
        }

        $this->resyncAfterSatelliteInstall();

        return $this;
    }

    /**
     * @return list<string>
     */
    private function resolveSatelliteSelections(): array
    {
        $available = $this->availableSatelliteKeys();

        if ($available === []) {
            return [];
        }

        $fromOption = $this->parseWithOption();

        if ($fromOption !== []) {
            $unknown = array_values(array_diff($fromOption, array_keys(self::SATELLITE_PACKAGES)));

            if ($unknown !== []) {
                $this->warn($this->messages->get('satellites_unknown', [
                    'packages' => implode(', ', $unknown),
                ]));
            }

            return array_values(array_intersect($fromOption, $available));
        }

        if ($this->shouldSkipPrompts()) {
            return [];
        }

        $options = [];

        foreach ($available as $key) {
            $options[$key] = self::SATELLITE_PACKAGES[$key]['label'];
        }

        /** @var list<string> $selected */
        $selected = multiselect(
            label: $this->messages->get('satellites_prompt'),
            options: $options,
            default: [],
            required: false,
            hint: $this->messages->get('satellites_hint'),
        );

        return array_values(array_intersect($selected, $available));
    }

    /**
     * @return list<string>
     */
    private function parseWithOption(): array
    {
        $raw = (string) $this->option('with');

        if (trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $value): string => strtolower(trim($value)),
            explode(',', $raw),
        )));
    }

    /**
     * @return list<string>
     */
    private function availableSatelliteKeys(): array
    {
        $keys = [];

        foreach (self::SATELLITE_PACKAGES as $key => $meta) {
            if ($this->isSatelliteInstalled($meta['package'])) {
                continue;
            }

            $keys[] = $key;
        }

        return $keys;
    }

    private function isSatelliteInstalled(string $package): bool
    {
        try {
            return InstalledVersions::isInstalled($package);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Re-run migrate / frontend-sync / npm in a fresh PHP process so newly
     * required packages are discovered.
     */
    private function resyncAfterSatelliteInstall(): void
    {
        $php = PHP_BINARY ?: 'php';
        $artisan = base_path('artisan');

        $steps = [
            [$php, $artisan, 'migrate', '--force', '--no-interaction'],
            [$php, $artisan, 'orbit:frontend-sync', '--no-interaction'],
        ];

        foreach ($steps as $command) {
            $this->line($this->messages->get('satellites_step_running', [
                'command' => implode(' ', array_slice($command, 2)),
            ]));

            $result = Process::path(base_path())->timeout(600)->run($command, function (string $type, string $buffer): void {
                $this->output->write($buffer);
            });

            if (! $result->successful()) {
                $this->warn($this->messages->get('satellites_step_failed', [
                    'command' => implode(' ', $command),
                ]));
            }
        }

        if (! $this->option('skip-npm')) {
            $this->buildFrontendAssets();
        }
    }

    private function finish(): void
    {
        $this->info($this->messages->get('completed'));
        $this->comment($this->messages->get('create_admin_hint'));
        $this->line($this->messages->get('serve_hint'));
    }

    /**
     * @return $this
     */
    private function promptForStargazer(InstallLocale $locale): self
    {
        if ($this->shouldSkipPrompts()) {
            return $this;
        }

        $messages = InstallMessages::for($locale);

        if (! $this->confirm($messages->get('show_love_confirm'))) {
            return $this;
        }

        $repo = self::REPOSITORY_URL;

        match (PHP_OS_FAMILY) {
            'Darwin'  => exec('open '.escapeshellarg($repo)),
            'Windows' => exec('start '.escapeshellarg($repo)),
            'Linux'   => exec('xdg-open '.escapeshellarg($repo)),
            default   => $this->line($messages->get('show_love_link', ['url' => $repo])),
        };

        $this->line($messages->get('show_love_thanks'));

        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return $this
     */
    private function executeCommand(string $command, array $parameters = []): self
    {
        try {
            $result = $this->callSilent($command, $parameters);
        } catch (\Exception $exception) {
            $result = 1;
            $this->alert($exception->getMessage());
        }

        if ($result) {
            $encodedParameters = http_build_query($parameters, '', ' ');
            $encodedParameters = str_replace('%5C', '/', $encodedParameters);
            $this->alert($this->messages->get('command_failed', [
                'command'    => $command,
                'parameters' => $encodedParameters,
            ]));
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function replaceInFiles(string $directory, string $search, string $replace): self
    {
        if (! is_dir($directory)) {
            return $this;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $filePath = $file->getRealPath();
            $fileContents = file_get_contents($filePath);

            if (! str_contains($fileContents, $search)) {
                continue;
            }

            file_put_contents($filePath, str_replace($search, $replace, $fileContents));
        }

        return $this;
    }
}
