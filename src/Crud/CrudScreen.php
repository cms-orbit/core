<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Crud;

use CmsOrbit\Core\Crud\Requests\ActionRequest;
use CmsOrbit\Core\Crud\Requests\DeleteRequest;
use CmsOrbit\Core\Crud\Requests\ForceDeleteRequest;
use CmsOrbit\Core\Crud\Requests\RestoreRequest;
use CmsOrbit\Core\Crud\Requests\UpdateRequest;
use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Foundation\Entity\EntityRegistry;
use CmsOrbit\Core\Screen\Action as ActionButton;
use CmsOrbit\Core\Screen\Actions\DropDown;
use CmsOrbit\Core\Screen\Screen;
use CmsOrbit\Core\Support\Facades\Toast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Generic CRUD screen that consumes an Entity descriptor instead of an inherited
 * model. Subclasses (List/Create/Edit/View) provide the per-action layout while
 * shared persistence behaviour lives here.
 */
abstract class CrudScreen extends Screen
{
    /**
     * Resolve the entity for the current route.
     */
    protected function entity(): Entity
    {
        return app(EntityRegistry::class)->findOrFail(request()->route('entity'));
    }

    public function name(): ?string
    {
        return $this->entity()->label();
    }

    public function permission(): ?iterable
    {
        return [$this->entity()->permissionKey()];
    }

    /**
     * The list-screen route for the current entity.
     */
    protected function listRoute(): string
    {
        return route('orbit.entities.'.$this->entity()::uriKey().'.index');
    }

    /**
     * Check a CRUD ability for the current user.
     */
    protected function can(string $ability): bool
    {
        $user = request()->user();

        if ($user === null) {
            return false;
        }

        if (! method_exists($user, 'hasAccess')) {
            return true;
        }

        return $user->hasAccess($this->entity()->abilityPermission($ability));
    }

    /**
     * Resolve the entity's bulk actions to instances.
     */
    protected function actions(): Collection
    {
        return collect($this->entity()->actions())
            ->map(fn ($action) => is_string($action) ? resolve($action) : $action);
    }

    /**
     * The selectable action buttons with their dispatch parameters bound.
     */
    protected function availableActions(): Collection
    {
        return $this->actions()
            ->map(fn (Action $action) => $action->button()
                ->method('action')
                ->parameters(array_merge(
                    $action->button()->get('parameters', []),
                    ['_action' => $action->name()]
                )))
            ->filter(fn (ActionButton $button) => $button->isSee());
    }

    /**
     * The "Actions" dropdown shown in the command bar.
     */
    protected function actionsButtons(): DropDown
    {
        $actions = $this->availableActions();

        return DropDown::make(__('Actions'))
            ->icon('bs.three-dots-vertical')
            ->canSee($actions->isNotEmpty())
            ->list($actions->toArray());
    }

    /**
     * Dispatch the selected bulk action.
     */
    public function action(ActionRequest $request)
    {
        $models = $request->models();

        if ($models->isEmpty()) {
            Toast::warning(__('No entries over which you can perform an action'));

            return back();
        }

        /** @var Action $action */
        $action = $this->actions()
            ->filter(fn (Action $action) => $action->name() === $request->query('_action'))
            ->whenEmpty(fn () => abort(405))
            ->first();

        return $action->handle($models);
    }

    public function update(UpdateRequest $request): RedirectResponse
    {
        $request->entity()->save($request, $request->findModelOrFail());

        Toast::info(__('The :resource was updated!', ['resource' => $request->entity()->singularLabel()]));

        return redirect()->route('orbit.entities.'.$request->entity()::uriKey().'.index');
    }

    public function delete(DeleteRequest $request): RedirectResponse
    {
        $request->entity()->delete($request->findModelOrFail());

        Toast::info(__('The :resource was deleted!', ['resource' => $request->entity()->singularLabel()]));

        return redirect()->route('orbit.entities.'.$request->entity()::uriKey().'.index');
    }

    public function forceDelete(ForceDeleteRequest $request): RedirectResponse
    {
        $request->entity()->forceDelete($request->findModelOrFail());

        Toast::info(__('The :resource was deleted!', ['resource' => $request->entity()->singularLabel()]));

        return redirect()->route('orbit.entities.'.$request->entity()::uriKey().'.index');
    }

    public function restore(RestoreRequest $request): RedirectResponse
    {
        $request->entity()->restore($request->findModelOrFail());

        Toast::info(__('The :resource was restored!', ['resource' => $request->entity()->singularLabel()]));

        return redirect()->route('orbit.entities.'.$request->entity()::uriKey().'.index');
    }

    /**
     * Whether the given model (or the screen model) is soft-deleted.
     */
    protected function isSoftDeleted(?Model $model = null): bool
    {
        $model ??= $this->model ?? null;

        return $this->entity()->softDeletes()
            && $model !== null
            && method_exists($model, 'trashed')
            && $model->trashed();
    }

    /**
     * The custom React page component for this CRUD action, when overridden on
     * the entity.
     */
    public function screenComponent(): ?string
    {
        return $this->entity()->screenComponent($this->crudAction());
    }

    /**
     * The CRUD action key (list/create/edit/view) for component overrides.
     */
    protected function crudAction(): string
    {
        return (string) Str::of(class_basename(static::class))
            ->before('Screen')
            ->lower();
    }
}
