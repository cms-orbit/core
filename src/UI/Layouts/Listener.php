<?php

namespace CmsOrbit\Core\Layouts;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use CmsOrbit\Core\UI\Builder;
use CmsOrbit\Core\UI\Layout;
use CmsOrbit\Core\UI\Repository;
use CmsOrbit\Core\Support\Facades\Dashboard;

abstract class Listener extends Layout
{
    /**
     * @var string
     */
    protected $template = 'settings::layouts.listener';

    /**
     * List of field names for which values will be listened.
     *
     * @var string[]
     */
    protected $targets = [];

    /**
     * @return array
     */
    abstract protected function layouts(): iterable;

    /**
     * @param \CmsOrbit\Core\UI\Repository $repository
     * @param \Illuminate\Http\Request  $request
     *
     * @return \CmsOrbit\Core\UI\Repository
     */
    abstract public function handle(Repository $repository, Request $request): Repository;

    /**
     * @return mixed|void
     */
    public function build(Repository $repository)
    {
        if (! $this->isSee()) {
            return;
        }

        $this->query = $repository;
        $this->layouts = $this->layouts();

        $this->variables = array_merge($this->variables, [
            'targets'    => collect($this->targets)->map(fn ($target) => Builder::convertDotToArray($target))->toJson(),
            'asyncRoute' => $this->asyncRoute(),
        ]);

        return $this->buildAsDeep($repository);
    }

    /**
     * Returns the system layer name.
     * Required to define an asynchronous layer.
     */
    public function getSlug(): string
    {
        return hash('xxh3', (static::class));
    }

    /**
     * Return URL for screen template requests from the browser.
     */
    protected function asyncRoute(): ?string
    {
        $screen = Dashboard::getCurrentScreen();

        if (! $screen) {
            return null;
        }

        return route('orbit.async.listener', [
            'screen' => Crypt::encryptString(get_class($screen)),
            'layout' => Crypt::encryptString(static::class),
        ]);
    }
}
