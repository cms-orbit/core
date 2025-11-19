<?php

namespace CmsOrbit\Core;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use CmsOrbit\Core\Commands\ActionCommand;
use CmsOrbit\Core\Commands\ResourceCommand;
use CmsOrbit\Core\Providers\FoundationServiceProvider;
use CmsOrbit\Core\Facades\Dashboard;

class CrudServiceProvider extends ServiceProvider
{
    /**
     * Path to crud dir
     *
     * @var string
     */
    protected $path;

    /**
     * The available command shortname.
     *
     * @var array
     */
    protected $commands = [
        ResourceCommand::class,
        ActionCommand::class,
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(ResourceFinder $finder, Arbitrator $arbitrator): void
    {
        $resources = $finder
            ->setNamespace(app()->getNamespace() . 'CmsOrbit\Core\\Resources')
            ->find(app_path('Orbit/Resources'));

        $arbitrator
            ->resources($resources)
            ->boot();

        Route::domain((string)config('settings.domain'))
            ->prefix(Dashboard::prefix('/'))
            ->as('settings.')
            ->middleware(config('settings.middleware.private'))
            ->group(__DIR__ . '/../routes/crud.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->path = dirname(__DIR__, 1);

        $this->commands($this->commands);
        $this->loadJsonTranslationsFrom($this->path.'/resources/lang/');
        $this->app->register(FoundationServiceProvider::class, true);

        $this->app->singleton(Arbitrator::class, static function () {
            return new Arbitrator();
        });
    }
}
