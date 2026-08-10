<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Entity;

use CmsOrbit\Core\Foundation\ItemPermission;
use CmsOrbit\Core\Screen\Actions\Menu;
use CmsOrbit\Core\Screen\Field;
use CmsOrbit\Core\Screen\Sight;
use CmsOrbit\Core\Screen\TD;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Entity is a model-NOT-inherited admin descriptor.
 *
 * Instead of subclassing the Eloquent model (as cms-orbit 3.1 did with
 * DynamicModel inheritance, or as a static $model property would imply), an
 * Entity points at a vanilla Eloquent model via {@see Model()} and reaches it
 * exclusively through {@see getModel()} / {@see query()}. This keeps the host
 * application's models untouched while the admin layer remains self-describing
 * and self-registering (menu / permissions / presenter / seo).
 */
abstract class Entity
{
    /**
     * The fully-qualified Eloquent model class this entity administers.
     *
     * @return class-string<Model>
     */
    abstract public function model(): string;

    /**
     * Resolve a fresh model instance through the entity.
     */
    public function getModel(): Model
    {
        return app($this->model());
    }

    /**
     * A new (unsaved) model instance, used by the create screen.
     */
    public function newModel(): Model
    {
        return $this->getModel()->newInstance();
    }

    /**
     * Base query builder reached through the entity.
     */
    public function query(): Builder
    {
        return $this->getModel()->newQuery();
    }

    /*
    |--------------------------------------------------------------------------
    | Developer overrides
    |--------------------------------------------------------------------------
    */

    /**
     * Form fields shown on the create / edit screens.
     *
     * @return Field[]
     */
    abstract public function fields(): array;

    /**
     * Table columns shown on the list screen.
     *
     * @return TD[]
     */
    public function columns(): array
    {
        return [];
    }

    /**
     * Read-only sights shown on the view screen.
     *
     * @return Sight[]
     */
    public function legend(): array
    {
        return [];
    }

    /**
     * List-screen filters.
     *
     * @return array<int, mixed>
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Columns searched by the table toolbar search box.
     *
     * @return array<int, string>
     */
    public function searchColumns(): array
    {
        return [];
    }

    /**
     * Bulk actions available on the list screen.
     *
     * @return array<int, mixed>
     */
    public function actions(): array
    {
        return [];
    }

    /**
     * Relations eager-loaded for index/detail queries.
     *
     * @return array<int, string>
     */
    public function with(): array
    {
        return [];
    }

    /**
     * Validation rules applied on save/update.
     *
     * @return array<string, mixed>
     */
    public function rules(Model $model): array
    {
        return [];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Custom validation attribute names.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Persistence hooks (override onSave/onDelete/onRestore/onForceDelete)
    |--------------------------------------------------------------------------
    */

    public function save(Request $request, Model $model): void
    {
        if (method_exists($this, 'onSave')) {
            $this->onSave($request, $model);

            return;
        }

        $model->forceFill($request->all())->save();
    }

    public function delete(Model $model): void
    {
        if (! $this->canDelete($model)) {
            throw ValidationException::withMessages([
                'resource' => $this->deleteBlockedMessage($model),
            ]);
        }

        if (method_exists($this, 'onDelete')) {
            $this->onDelete($model);

            return;
        }

        $model->delete();
    }

    public function canDelete(Model $model): bool
    {
        return true;
    }

    public function deleteBlockedMessage(Model $model): string
    {
        return __('This resource cannot be deleted.');
    }

    public function restore(Model $model): void
    {
        if (method_exists($this, 'onRestore')) {
            $this->onRestore($model);

            return;
        }

        $model->restore();
    }

    public function forceDelete(Model $model): void
    {
        if (method_exists($this, 'onForceDelete')) {
            $this->onForceDelete($model);

            return;
        }

        $model->forceDelete();
    }

    /*
    |--------------------------------------------------------------------------
    | Automatic metadata (self-describing)
    |--------------------------------------------------------------------------
    */

    /**
     * Base class name without the "Entity"/"Resource" suffix.
     */
    public static function nameWithoutSuffix(): string
    {
        return (string) Str::of(static::class)
            ->classBasename()
            ->replaceMatches('/(Entity|Resource)$/', '')
            ->whenEmpty(fn () => Str::of('Entity'));
    }

    /**
     * Plural kebab URI key used in routes / permissions (e.g. "posts").
     */
    public static function uriKey(): string
    {
        return (string) Str::of(static::nameWithoutSuffix())->kebab()->plural();
    }

    /**
     * Human plural label.
     */
    public function label(): string
    {
        $key = (string) Str::of(static::nameWithoutSuffix())->snake(' ')->title()->plural();

        return (string) __($key);
    }

    /**
     * Human singular label.
     */
    public function singularLabel(): string
    {
        $key = (string) Str::of(static::nameWithoutSuffix())->snake(' ')->title()->singular();

        return (string) __($key);
    }

    /**
     * Sidebar icon.
     */
    public function icon(): string
    {
        return 'bs.collection';
    }

    /**
     * Sidebar section heading.
     */
    public function section(): string
    {
        return __('Entities');
    }

    /**
     * Stable section identifier used to group menu items and resolve the
     * section icon from {@see Orbit::registerSection()}. When null, items are
     * grouped by the translated {@see section()} label only.
     */
    public function sectionKey(): ?string
    {
        return null;
    }

    /**
     * Sort weight (lower = higher in the menu).
     */
    public function sort(): int
    {
        return 2000;
    }

    /**
     * Pagination size for the list screen.
     */
    public function perPage(): int
    {
        return 25;
    }

    /**
     * Allowed page sizes for the list table footer.
     *
     * @return array<int, int>
     */
    public function perPageOptions(): array
    {
        $default = $this->perPage();

        return collect([10, 25, 50, 100, $default])
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Whether the entity should appear in the navigation menu.
     */
    public function displayInNavigation(): bool
    {
        return true;
    }

    /**
     * Optional parent menu slug used to nest the entity under a group item.
     */
    public function menuParent(): ?string
    {
        return null;
    }

    /**
     * Permission slug prefix submitted to Core (e.g. "orbit.entities.posts").
     */
    public function permissionKey(): string
    {
        return 'orbit.entities.'.static::uriKey();
    }

    /**
     * Whether the underlying model uses soft deletes.
     */
    public function softDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->model()), true);
    }

    /**
     * The CRUD surface area this entity exposes — the single source of truth for
     * both the registered routes ({@see routes/orbit.php}) and the submitted
     * permission set ({@see permissions()}). Defaults are inferred from the
     * model: soft-deletable models additionally get the trash listing plus
     * restore/forceDelete; others stop at a hard delete.
     *
     * The `orbit:entity` generator writes this list into the scaffolded entity
     * so each entity can drop or add features in its own directory (e.g. a
     * read-only entity returns just ['list', 'view']).
     *
     * Recognised features: list, create, view, edit, delete, restore,
     * forceDelete, trash.
     *
     * @return array<int, string>
     */
    public function crud(): array
    {
        $features = ['list', 'create', 'view', 'edit', 'delete'];

        if ($this->softDeletes()) {
            $features = array_merge($features, ['restore', 'forceDelete', 'trash']);
        }

        return $features;
    }

    /**
     * Whether a given CRUD feature is enabled for this entity.
     */
    public function hasCrud(string $feature): bool
    {
        return in_array($feature, $this->crud(), true);
    }

    /**
     * The permission set submitted to Core, derived from {@see crud()}. Features
     * that are not permission-bearing (view/trash) reuse the base list/restore
     * permissions and add no extra slug.
     */
    public function permissions(): ItemPermission
    {
        $base = $this->permissionKey();

        $group = ItemPermission::group($this->label());

        $map = [
            'list'        => [$base, __('List')],
            'create'      => [$base.'.create', __('Create')],
            'edit'        => [$base.'.edit', __('Edit')],
            'delete'      => [$base.'.delete', __('Delete')],
            'forceDelete' => [$base.'.forceDelete', __('Force delete')],
            'restore'     => [$base.'.restore', __('Restore')],
        ];

        foreach ($this->crud() as $feature) {
            if (isset($map[$feature])) {
                $group->addPermission($map[$feature][0], $map[$feature][1]);
            }
        }

        return $group;
    }

    /**
     * Map a CRUD ability to its permission slug.
     */
    public function abilityPermission(string $ability): string
    {
        $base = $this->permissionKey();

        return match ($ability) {
            'create'         => $base.'.create',
            'update', 'edit' => $base.'.edit',
            'delete'         => $base.'.delete',
            'forceDelete'    => $base.'.forceDelete',
            'restore'        => $base.'.restore',
            default          => $base, // viewAny / view
        };
    }

    /**
     * Build the navigation menu item(s) for this entity. The href is resolved at
     * registration time; the permission slug is filtered per-request.
     *
     * @return Menu[]
     */
    public function menu(): array
    {
        if (! $this->displayInNavigation()) {
            return [];
        }

        $routeName = 'orbit.entities.'.static::uriKey().'.index';
        $url = Route::has($routeName) ? route($routeName) : '#';

        $menu = Menu::make($this->label())
            ->icon($this->icon())
            ->url($url)
            ->sort($this->sort())
            ->set('section', $this->section())
            ->set('permission', $this->permissionKey());

        if ($this->sectionKey() !== null) {
            $menu->set('sectionKey', $this->sectionKey());
        }

        if ($this->menuParent() !== null) {
            $menu->parent($this->menuParent());
        }

        return [$menu];
    }

    /**
     * Presenter metadata used by lists, search and headers.
     *
     * @return array{label: string, title: string, subTitle: ?string, url: ?string}
     */
    public function presenter(Model $model): array
    {
        $title = (string) ($model->getAttribute('title')
            ?? $model->getAttribute('name')
            ?? $this->singularLabel().' #'.$model->getKey());

        return [
            'label'    => $this->singularLabel(),
            'title'    => $title,
            'subTitle' => (string) $model->getKey(),
            'url'      => $this->showUrl($model),
        ];
    }

    /**
     * The admin view URL for a model row.
     */
    public function showUrl(Model $model): ?string
    {
        return route('orbit.entities.'.static::uriKey().'.view', [
            'id' => $model->getKey(),
        ]);
    }

    /**
     * URLs contributed by this entity to the sitemap. Returns an empty list by
     * default (admin entities have no public URLs); content entities override
     * this to yield their public show URLs.
     *
     * @return iterable<int, array<string, mixed>>
     */
    public function sitemapUrls(): iterable
    {
        return [];
    }

    /**
     * Default SEO meta extracted from the model. Override to customise.
     *
     * @return array<string, mixed>
     */
    public function seo(Model $model): array
    {
        return [
            'title'       => $model->getAttribute('title') ?? $model->getAttribute('name'),
            'description' => $model->getAttribute('description')
                ?? $model->getAttribute('excerpt'),
            'thumbnail' => $model->getAttribute('thumbnail')
                ?? $model->getAttribute('image'),
            'slug' => $model->getAttribute('slug'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Screen / component overrides (escape hatch)
    |--------------------------------------------------------------------------
    */

    /**
     * Override the generic CRUD screens. Return a map keyed by
     * list/create/edit/view with screen class names.
     *
     * @return array<string, class-string>
     */
    public function screens(): array
    {
        return [];
    }

    /**
     * Resolve the screen class for a given action, falling back to the generic
     * CRUD screen when not overridden.
     *
     * @param class-string $default
     *
     * @return class-string
     */
    public function screenFor(string $action, string $default): string
    {
        return $this->screens()[$action] ?? $default;
    }

    /**
     * A fully custom React page component name for a given action (escape
     * hatch). When non-null the React renderer hands props directly to it.
     */
    public function screenComponent(string $action): ?string
    {
        return null;
    }
}
