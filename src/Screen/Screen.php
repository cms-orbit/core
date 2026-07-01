<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Screen;

use CmsOrbit\Core\Foundation\Http\Controllers\Controller;
use CmsOrbit\Core\Screen\Concerns\HasCommandBar;
use CmsOrbit\Core\Screen\Concerns\HasFillablePublicProperties;
use CmsOrbit\Core\Screen\Concerns\InteractsWithEncryptedState;
use CmsOrbit\Core\Screen\Concerns\ModelStateRetrievable;
use CmsOrbit\Core\Screen\Layouts\Listener;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tabuna\Breadcrumbs\Breadcrumbs;
use Tabuna\Breadcrumbs\Crumb;

/**
 * Class Screen.
 *
 * This is the main class for creating screens in the Orbit. A screen is a web page
 * that displays content and allows for user interaction.
 */
abstract class Screen extends Controller
{
    use HasCommandBar,
        HasFillablePublicProperties,
        InteractsWithEncryptedState,
        ModelStateRetrievable;

    /**
     * @param  mixed  ...$arguments
     * @return mixed
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     *
     * @see static::handle()
     */
    public function __invoke(Request $request, ...$arguments)
    {
        return $this->handle($request, ...$arguments);
    }

    /**
     * The base view that will be rendered.
     */
    public function screenBaseView(): string
    {
        return 'orbit::layouts.base';
    }

    /**
     * The name of the screen to be displayed in the header.
     */
    public function name(): ?string
    {
        return $this->name ?? null;
    }

    /**
     * A description of the screen to be displayed in the header.
     */
    public function description(): ?string
    {
        return $this->description ?? null;
    }

    /**
     * The permissions required to access this screen.
     */
    public function permission(): ?iterable
    {
        return isset($this->permission)
            ? Arr::wrap($this->permission)
            : null;
    }

    /**
     * The layout for this screen, consisting of a collection of views.
     *
     * @return iterable<Layout>|iterable<string>
     */
    abstract public function layout(): iterable;

    /**
     * Builds the screen using the given data repository.
     *
     *
     * @return View
     */
    public function build(Repository $repository)
    {
        return LayoutFactory::blank([
            $this->layout(),
        ])->build($repository);
    }

    /**
     * Builds the screen asynchronously using the given method and template slug.
     *
     *
     * @return Response
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function asyncBuild(string $method, string $slug)
    {
        Orbit::setCurrentScreen($this, true);

        abort_unless(
            static::getAvailableMethods()->contains($method),
            Response::HTTP_BAD_REQUEST,
            "Async method '{$method}' is unavailable."
        );

        abort_unless($this->checkAccess(request()), static::unaccessed());

        $state = $this->extractState();
        $this->fillPublicProperty($state);

        $parameters = request()->collect()->merge([
            'state' => $state,
        ])->all();

        $repository = $this->callMethod($method, $parameters);

        if (is_array($repository)) {
            $repository = new Repository(array_merge($state->all(), $repository));
        }

        $view = $this->view($repository)
            ->fragments(collect($slug)->push('screen-state')->all());

        return response($view)
            ->header('Content-Type', 'text/vnd.turbo-stream.html');
    }

    /**
     * Builds the screen asynchronously using listeners
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function asyncPartialLayout(Listener $layout, Request $request): Response
    {
        Orbit::setCurrentScreen($this, true);

        abort_unless($this->checkAccess(request()), static::unaccessed());

        $state = $this->extractState();
        $this->fillPublicProperty($state);

        $repository = $layout->handle($state, $request);

        $view = $layout->build($repository).view('orbit::partials.state', [
            'state' => $this->serializableState(),
        ]);

        return response($view)
            ->header('Content-Type', 'text/vnd.turbo-stream.html');
    }

    /**
     * Async modal loader: return a single layout subtree (by slug) as JSON.
     * Replaces the Turbo-stream asyncBuild for the Inertia/React frontend.
     *
     *
     * @return array<string, mixed>
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function asyncBuildArray(string $method, string $slug): array
    {
        Orbit::setCurrentScreen($this, true);

        abort_unless(
            static::getAvailableMethods()->contains($method),
            Response::HTTP_BAD_REQUEST,
            "Async method '{$method}' is unavailable."
        );

        abort_unless($this->checkAccess(request()), static::unaccessed());

        $state = $this->extractState();
        $this->fillPublicProperty($state);

        $parameters = request()->collect()->merge(['state' => $state])->all();

        $repository = $this->callMethod($method, $parameters);

        if (is_array($repository)) {
            $repository = new Repository(array_merge($state->all(), $repository));
        }

        $layout = LayoutFactory::blank([$this->layout()])->findBySlug($slug);

        return [
            'layout' => $layout?->toArray($repository),
            'data' => $this->serializeRepository($repository),
            'state' => $this->serializableState(),
        ];
    }

    /**
     * Listener partial reload: re-run the listener and return its layout
     * subtree as JSON. Replaces the Turbo-stream asyncPartialLayout.
     *
     *
     * @return array<string, mixed>
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function asyncPartialLayoutArray(Listener $layout, Request $request): array
    {
        Orbit::setCurrentScreen($this, true);

        abort_unless($this->checkAccess(request()), static::unaccessed());

        $state = $this->extractState();
        $this->fillPublicProperty($state);

        $repository = $layout->handle($state, $request);

        return [
            'layout' => $layout->toArray($repository),
            'data' => $this->serializeRepository($repository),
            'state' => $this->serializableState(),
        ];
    }

    /**
     * Render the screen as an Inertia response consumed by the React
     * ScreenRenderer. The Blade rendering pipeline is replaced by the
     * serialization layer (see CONTRACT.md).
     *
     *
     * @return \Inertia\Response
     *
     * @throws \Throwable
     */
    public function view(array|Repository $httpQueryArguments = [])
    {
        $repository = is_a($httpQueryArguments, Repository::class)
            ? $httpQueryArguments
            : $this->buildQueryRepository($httpQueryArguments);

        return Inertia::render('orbit/screen', [
            'name' => $this->name(),
            'description' => $this->description(),
            'permission' => $this->permission(),
            'breadcrumbs' => $this->buildBreadcrumbs(),
            'commandBar' => $this->buildCommandBarArray($repository),
            'layout' => $this->buildLayoutTree($repository),
            'data' => $this->serializeRepository($repository),
            'state' => $this->serializableState(),
            'screenComponent' => $this->screenComponent(),
            'formValidateMessage' => $this->formValidateMessage(),
            'needPreventsAbandonment' => $this->needPreventsAbandonment(),
        ]);
    }

    /**
     * The custom full-screen React component name. When non-null the React
     * renderer hands props directly to this component instead of the automatic
     * layout-tree renderer (escape hatch).
     */
    public function screenComponent(): ?string
    {
        return null;
    }

    /**
     * Build the serialized layout tree for the React renderer.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildLayoutTree(Repository $repository): array
    {
        return collect($this->layout())
            ->map(fn ($layout) => is_object($layout) ? $layout : resolve($layout))
            ->filter(fn (Layout $layout) => $layout->isSee())
            ->map(fn (Layout $layout) => $layout->toArray($repository))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Serialize the screen repository (query() result) into Inertia props.
     *
     * @return array<string, mixed>
     */
    protected function serializeRepository(Repository $repository): array
    {
        return collect($repository->toArray())
            ->map(fn ($value) => $value instanceof Arrayable ? $value->toArray() : $value)
            ->all();
    }

    /**
     * Build breadcrumbs for the current route via the Breadcrumbs registrar.
     *
     * @return array<int, array{label: string, url: string|null}>
     */
    protected function buildBreadcrumbs(): array
    {
        if (! Breadcrumbs::has()) {
            return [];
        }

        return Breadcrumbs::current()
            ->map(fn (Crumb $crumb) => [
                'label' => $crumb->title(),
                'url' => $crumb->url(),
            ])
            ->values()
            ->all();
    }

    /**
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    protected function buildQueryRepository(array $httpQueryArguments = []): Repository
    {
        $query = $this->callMethod('query', $httpQueryArguments);

        return tap(new Repository($query), fn (Repository $repository) => $this->fillPublicProperty($repository));
    }

    /**
     * Response or HTTP code that will be returned if user does not have access to the screen.
     *
     * @return int | \Symfony\Component\HttpFoundation\Response
     */
    public static function unaccessed()
    {
        return Response::HTTP_FORBIDDEN;
    }

    /**
     * @return RedirectResponse|mixed
     *
     * @throws BindingResolutionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public function handle(Request $request, ...$arguments)
    {
        Orbit::setCurrentScreen($this);

        $method = $request->route()->parameter('method', 'view');

        if (! $request->isMethodSafe()) {
            $method = Arr::last($request->route()->parameters(), null, 'view');
        }

        $state = $this->extractState();
        $this->fillPublicProperty($state);

        // Deny access without rights
        abort_unless($this->checkAccess($request), static::unaccessed());

        // Redirect for correct residual behavior
        if ($request->isMethodSafe() && $method !== 'view') {
            return redirect()->action([static::class], $request->all());
        }

        return $this->callMethod($method, $arguments) ?? $this->backWithCurrentState();
    }

    /**
     * Determine if the user is authorized and has the required rights to complete this request.
     */
    protected function checkAccess(Request $request): bool
    {
        $user = $request->user();

        if ($user === null) {
            return true;
        }

        return $user->hasAnyAccess($this->permission());
    }

    /**
     * This method returns a localized string message indicating that the user should check the entered data,
     * and that it may be necessary to specify the data in other languages.
     */
    public function formValidateMessage(): string
    {
        return __('Unable to start action. Please check your input and try again.');
    }

    /**
     * The boolean value returned is true, indicating that the form is preventing abandonment.
     */
    public function needPreventsAbandonment(): bool
    {
        return config('orbit.prevents_abandonment', true);
    }

    /**
     * Calls the specified method with the given parameters.
     *
     *
     * @return mixed
     *
     * @throws \ReflectionException
     * @throws BindingResolutionException
     */
    private function callMethod(string $method, array $parameters = [])
    {
        $uses = static::class.'@'.$method;

        $preparedParameters = self::prepareForExecuteMethod($uses);

        return App::call($uses, $preparedParameters ?? $parameters);
    }

    /**
     * Prepare the method execution by binding route parameters and substituting implicit bindings.
     */
    public static function prepareForExecuteMethod(string $uses): ?array
    {
        $route = request()->route();

        if ($route === null) {
            return null;
        }

        collect(request()->query())->each(function ($value, string $key) use ($route) {
            $route->setParameter($key, $value);
        });

        $original = $route->action['uses'];

        $route = $route->uses($uses);

        Route::substituteImplicitBindings($route);

        $parameters = $route->parameters();

        $route->uses($original);

        return $parameters;
    }

    /**
     * Get can transfer to the screen only
     * user-created methods available in it.
     */
    public static function getAvailableMethods(): Collection
    {
        $class = (new \ReflectionClass(static::class))
            ->getMethods(\ReflectionMethod::IS_PUBLIC);

        return collect($class)
            ->mapWithKeys(fn (\ReflectionMethod $method) => [$method->name => $method])
            ->except(get_class_methods(Screen::class))
            ->except(['query'])
            /*
             * Route filtering requires at least one element to be present.
             * We set __invoke by default, since it must be public.
             */
            ->whenEmpty(fn () => collect('__invoke'))
            ->keys();
    }

    /**
     * Return to the previous state with the current object properties.
     *
     * @throws PhpVersionNotSupportedException
     */
    private function backWithCurrentState(): RedirectResponse
    {
        $properties = collect((new \ReflectionClass(static::class))
            ->getProperties(\ReflectionProperty::IS_PUBLIC))
            ->map(fn (\ReflectionProperty $property) => $property->getName())
            ->toArray();

        $currentState = collect(get_object_vars($this))
            ->only($properties);

        if ($currentState->isEmpty()) {
            return back();
        }

        return back()->with('_state', $this->serializableState());
    }

    /**
     * @deprecated
     *
     * @throws PhpVersionNotSupportedException
     */
    public function backWith(array $data): RedirectResponse
    {
        $this->fillPublicProperty(new Repository($data));

        return back()->with('_state', $this->serializableState());
    }

    /**
     * Returns the name of the base Stimulus controller for the frontend.
     *
     * This method is used to determine the base Stimulus controller that will be
     * utilized on the frontend of the application. The controller manages the
     * behavior of UI elements, interacting with other components via Hotwire.
     *
     * @return string The name of the base controller.
     */
    public function frontendController(): string
    {
        return 'base';
    }
}
