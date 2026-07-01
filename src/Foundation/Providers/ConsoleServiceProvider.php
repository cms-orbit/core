<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Providers;

use CmsOrbit\Core\Foundation\Commands\AdminCommand;
use CmsOrbit\Core\Foundation\Commands\ChartCommand;
use CmsOrbit\Core\Foundation\Commands\DocumentMakeCommand;
use CmsOrbit\Core\Foundation\Commands\EntityMakeCommand;
use CmsOrbit\Core\Foundation\Commands\FieldCommand;
use CmsOrbit\Core\Foundation\Commands\FilterCommand;
use CmsOrbit\Core\Foundation\Commands\FreshSuperAdminRoleCommand;
use CmsOrbit\Core\Foundation\Commands\InstallCommand;
use CmsOrbit\Core\Foundation\Commands\ListenerCommand;
use CmsOrbit\Core\Foundation\Commands\PresenterCommand;
use CmsOrbit\Core\Foundation\Commands\PublishCommand;
use CmsOrbit\Core\Foundation\Commands\RowsCommand;
use CmsOrbit\Core\Foundation\Commands\ScreenCommand;
use CmsOrbit\Core\Foundation\Commands\SelectionCommand;
use CmsOrbit\Core\Foundation\Commands\SitemapRefreshCommand;
use CmsOrbit\Core\Foundation\Commands\StubPublishCommand;
use CmsOrbit\Core\Foundation\Commands\TableCommand;
use CmsOrbit\Core\Foundation\Commands\TabMenuCommand;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;

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
        EntityMakeCommand::class,
        DocumentMakeCommand::class,
        FreshSuperAdminRoleCommand::class,
        SitemapRefreshCommand::class,
    ];

    public function boot(): void
    {
        AboutCommand::add('Orbit', fn () => [
            'Version' => Orbit::version(),
            'Access Mode' => config('orbit.access.mode'),
            'Prefix' => Orbit::prefix(),
        ]);

        $this
            ->registerMigrationsPublisher()
            ->registerTranslationsPublisher()
            ->registerConfigPublisher()
            ->registerAssetsPublisher()
            ->commands($this->commands);
    }

    /**
     * Publish the brand assets (logos, symbol, favicons) to the public path.
     *
     * @return $this
     */
    protected function registerAssetsPublisher(): self
    {
        $this->publishes([
            Orbit::path('resources/assets') => public_path('vendor/orbit'),
        ], 'orbit-assets');

        return $this;
    }

    /**
     * Register migrate.
     *
     * @return $this
     */
    protected function registerMigrationsPublisher(): self
    {
        $this->publishes([
            Orbit::path('database/migrations') => database_path('migrations'),
        ], 'orbit-migrations');

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
            Orbit::path('resources/lang') => lang_path('vendor/orbit'),
        ], 'orbit-lang');

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
            Orbit::path('resources/views') => resource_path('views/vendor/orbit'),
        ], 'orbit-views');

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
            Orbit::path('config/orbit.php') => config_path('orbit.php'),
        ], 'orbit-config');

        return $this;
    }
}
