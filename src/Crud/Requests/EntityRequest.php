<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Crud\Requests;

use CmsOrbit\Core\Crud\Layouts\ResourceFields;
use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Foundation\Entity\EntityRegistry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Base request shared by every CRUD screen. Resolves the Entity descriptor from
 * the route and exposes model lookups, queries and Core-permission checks. The
 * "model." prefix mirrors the original crud package field binding.
 */
class EntityRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * The entity targeted by the current route (uriKey baked in as a default).
     */
    public function entity(): Entity
    {
        return app(EntityRegistry::class)->findOrFail($this->route('entity'));
    }

    /**
     * A fresh (unsaved) model instance.
     */
    public function model(): Model
    {
        return $this->entity()->newModel();
    }

    public function findModel(): ?Model
    {
        return $this->getModelQuery()->find($this->route('id'));
    }

    public function findModelOrFail(): Model
    {
        return $this->getModelQuery()->findOrFail($this->route('id'));
    }

    /**
     * Query used for single-model lookups (includes trashed rows so the view /
     * edit screens can act on soft-deleted records).
     */
    public function getModelQuery(): Builder
    {
        $entity = $this->entity();
        $query = $entity->query();

        if ($entity->softDeletes()) {
            $query->withTrashed();
        }

        return $query->with($entity->with());
    }

    /**
     * Paginated, filtered list for the index screen.
     */
    public function getModelPaginationList(): LengthAwarePaginator
    {
        $entity = $this->entity();
        $query = $entity->query()->with($entity->with());

        if (method_exists($query->getModel(), 'scopeFilters')) {
            $query = $query->filters()->filtersApply($entity->filters());
        }

        return $query->paginate($entity->perPage());
    }

    /**
     * Check a CRUD ability against the entity's Core permission slugs.
     */
    public function can(string $ability): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if (! method_exists($user, 'hasAccess')) {
            return true;
        }

        return $user->hasAccess($this->entity()->abilityPermission($ability));
    }

    /**
     * Custom validation messages keyed under the model prefix.
     */
    public function messages(): array
    {
        return collect($this->entity()->messages())
            ->mapWithKeys(fn ($value, $key) => [ResourceFields::PREFIX.'.'.$key => $value])
            ->all();
    }

    /**
     * Custom attribute names keyed under the model prefix.
     */
    public function attributes(): array
    {
        return collect($this->input(ResourceFields::PREFIX, []))
            ->keys()
            ->mapWithKeys(fn ($key) => [$key => $key])
            ->merge($this->entity()->attributes())
            ->mapWithKeys(fn ($value, $key) => [ResourceFields::PREFIX.'.'.$key => $value])
            ->all();
    }

    /**
     * Flatten the validated "model." payload into the model attributes for
     * persistence (mirrors the original crud ResourceRequest::withValidator).
     * The validator already holds its own copy of the data (keyed "model.*"),
     * so reshaping the request bag here only affects post-validation reads.
     */
    public function withValidator($validator): void
    {
        $data = Arr::wrap($this->input(ResourceFields::PREFIX, []));

        collect($this->query->all())
            ->keys()
            ->filter(fn (string $key) => Str::startsWith($key, '_'))
            ->each(fn (string $key) => $this->query->remove($key));

        collect($this->all())->keys()->each(fn (string $key) => $this->offsetUnset($key));

        $this->replace($data);
    }
}
