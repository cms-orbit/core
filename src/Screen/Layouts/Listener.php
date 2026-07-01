<?php

namespace CmsOrbit\Core\Screen\Layouts;

use CmsOrbit\Core\Screen\Builder;
use CmsOrbit\Core\Screen\Layout;
use CmsOrbit\Core\Screen\Repository;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

abstract class Listener extends Layout
{
    /**
     * @var string
     */
    protected $template = 'orbit::layouts.listener';

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
            'targets' => collect($this->targets)->map(fn ($target) => Builder::convertDotToArray($target))->toJson(),
            'asyncRoute' => $this->asyncRoute(),
        ]);

        return $this->buildAsDeep($repository);
    }

    /**
     * Serialize the listener metadata. The watched field names and the async
     * partial-reload route are exposed so the React ListenerLayout can issue a
     * partial reload when a watched field changes. Children are populated here
     * (before serializeChildren() runs) from layouts().
     *
     * @return array<string, mixed>
     */
    protected function serialize(Repository $repository): array
    {
        $this->query = $repository;
        $this->layouts = $this->layouts();

        return [
            'targets' => collect($this->targets)
                ->map(fn ($target) => Builder::convertDotToArray($target))
                ->toJson(),
            'asyncRoute' => $this->asyncRoute(),
        ];
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
        $screen = Orbit::getCurrentScreen();

        if (! $screen) {
            return null;
        }

        return route('orbit.async.listener', [
            'screen' => Crypt::encryptString(get_class($screen)),
            'layout' => Crypt::encryptString(static::class),
        ]);
    }
}
