<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Providers;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use CmsOrbit\Core\Settings\Commands\AdminCommand;
use CmsOrbit\Core\Settings\Commands\ChartCommand;
use CmsOrbit\Core\Settings\Commands\FieldCommand;
use CmsOrbit\Core\Settings\Commands\FilterCommand;
use CmsOrbit\Core\Settings\Commands\InstallCommand;
use CmsOrbit\Core\Settings\Commands\ListenerCommand;
use CmsOrbit\Core\Settings\Commands\PresenterCommand;
use CmsOrbit\Core\Settings\Commands\PublishCommand;
use CmsOrbit\Core\Settings\Commands\RowsCommand;
use CmsOrbit\Core\Settings\Commands\ScreenCommand;
use CmsOrbit\Core\Settings\Commands\SelectionCommand;
use CmsOrbit\Core\Settings\Commands\StubPublishCommand;
use CmsOrbit\Core\Settings\Commands\TableCommand;
use CmsOrbit\Core\Settings\Commands\TabMenuCommand;
use CmsOrbit\Core\Support\Facades\Dashboard;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * The available command shortname.
     *
     * @var array
     */
    protected $commands = [
        InstallCommand::class,
        PublishCommand::class,
        AdminCommand::class,
        FilterCommand::class,
        RowsCommand::class,
        ScreenCommand::class,
        TableCommand::class,
        ChartCommand::class,
        SelectionCommand::class,
        ListenerCommand::class,
        PresenterCommand::class,
        TabMenuCommand::class,
        StubPublishCommand::class,
        FieldCommand::class,
    ];

    public function boot(): void
    {
        AboutCommand::add('Orbit Settings', fn () => [
            'Version'       => Dashboard::version(),
            'Domain'        => config('settings.domain'),
            'Prefix'        => config('settings.prefix'),
            'Assets Status' => Dashboard::assetsAreCurrent() ? '<fg=green;options=bold>CURRENT</>' : '<fg=yellow;options=bold>OUTDATED</>',
        ]);

        $this
            ->registerMigrationsPublisher()
            ->registerTranslationsPublisher()
            ->registerConfigPublisher()
            ->registerOrbitPublisher()
            ->registerViewsPublisher()
            ->registerAssetsPublisher()
            ->commands($this->commands);
    }

    /**
     * Register migrate.
     *
     * @return $this
     */
    protected function registerMigrationsPublisher(): self
    {
        $this->publishes([
            Dashboard::path('database/migrations') => database_path('migrations'),
        ], 'orchid-migrations');

        return $this;
    }

    /**
     * Register translations.
     *
     * @return $this
     */
    public function registerTranslationsPublisher(): self
    {
        $this->publishes([
            Dashboard::path('resources/lang') => lang_path('vendor/platform'),
        ], 'orchid-lang');

        return $this;
    }

    /**
     * Register views & Publish views.
     *
     * @return $this
     */
    public function registerViewsPublisher(): self
    {
        $this->publishes([
            Dashboard::path('resources/views') => resource_path('views/vendor/platform'),
        ], 'orchid-views');

        return $this;
    }

    /**
     * Register config.
     *
     * @return $this
     */
    protected function registerConfigPublisher(): self
    {
        $this->publishes([
            Dashboard::path('config/settings.php') => config_path('settings.php'),
        ], 'orchid-config');

        return $this;
    }

    /**
     * Register orchid.
     *
     * @return $this
     */
    protected function registerOrbitPublisher(): self
    {
        $this->publishes([
            Dashboard::path('stubs/app/routes/') => base_path('routes'),
            Dashboard::path('stubs/app/Orbit/') => app_path('Orbit'),
        ], 'orchid-app-stubs');

        return $this;
    }

    /**
     * Register the asset publishing configuration.
     *
     * @return $this
     */
    protected function registerAssetsPublisher(): self
    {
        $this->publishes([
            Dashboard::path('public') => public_path('vendor/orchid'),
        ], ['orchid-assets', 'laravel-assets']);

        return $this;
    }
}
