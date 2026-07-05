---
name: orbit-entity-development
description: Create or modify Orbit Entity descriptors, CRUD screens, permissions, and admin menus. Activate when building admin resources, entity fields/columns/filters, DocumentEntity types, or registering entities from host apps or satellite packages.
---

# Orbit Entity development

## When to use

- Adding a new admin resource (CRUD) to Orbit
- Extending `Entity` or `DocumentEntity` in a host app or package
- Registering menus, permissions, or filters for an entity

## Workflow

1. Create the Eloquent model + migration/factory in the **package or host app** (never in the wrong repo).
2. Create an Entity class extending `CmsOrbit\Core\Foundation\Entity\Entity` (or `DocumentEntity`).
3. Implement `model()`, `fields()`, `columns()`, and optionally `filters()`, `menu()`, `permission()`.
4. Wrap every user-facing label with `__()` and add keys to `resources/lang/ko.json`.
5. Register the entity:
   - Host app: `Orbit::registerEntities(base_path('entities'))` in `App\Orbit\OrbitProvider`
   - Package: `EntityRegistry::registerClass([MyEntity::class])` in `register()` via `afterResolving`
6. Prefer built-in fields (`Input`, `Select`, `Upload`, `LocaleTabs`, etc.) before custom React fields.
7. Add or update a Pest feature test that hits the generated CRUD routes or screen payload.

## Package rules

- Keep entity classes in the package namespace (`CmsOrbit\{Package}\Entities\...`).
- Do not import host `App\` classes from a package.
- If the entity exposes public Inertia pages, add `resources/orbit/frontend.json` and document
  `php artisan orbit:frontend-sync` in the README.

## Example

```php
use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Screen\Fields\Input;
use CmsOrbit\Core\Screen\TD;

class PostEntity extends Entity
{
    public function model(): string
    {
        return Post::class;
    }

    public function fields(): array
    {
        return [
            Input::make('title')->title(__('Title'))->required(),
        ];
    }

    public function columns(): array
    {
        return [
            TD::make('title', __('Title')),
        ];
    }
}
```
