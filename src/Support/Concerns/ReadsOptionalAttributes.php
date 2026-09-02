<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Support\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Read an attribute that a model may or may not have.
 *
 * Orbit repeatedly probes host models for conventional attributes it cannot
 * require — a `title` for a presenter label, a `profile_photo_url` for an
 * avatar. Calling `getAttribute()` for those is fine by default, but throws
 * once the host enables `Model::preventAccessingMissingAttributes()` (part of
 * the documented `Model::shouldBeStrict()` set):
 *
 *     MissingAttributeException: The attribute [title] either does not exist
 *     or was not retrieved for model [App\Models\InventoryLot].
 *
 * The guard only fires for models loaded from the database, so these crashes
 * surface in production paths (list screens, activity logging, the admin
 * shell) rather than in unit tests over freshly built models.
 */
trait ReadsOptionalAttributes
{
    /**
     * The attribute value, or null when the model does not expose it.
     */
    protected function optionalAttribute(Model $model, string $key): mixed
    {
        return $this->modelExposesAttribute($model, $key)
            ? $model->getAttribute($key)
            : null;
    }

    /**
     * Whether reading `$key` off `$model` is safe.
     *
     * `Model::hasAttribute()` answers exactly this, but it only landed during
     * the 11.x cycle and this package supports `laravel/framework: ^11.0`.
     * The fallback mirrors what it checks, minus custom cast classes — which
     * cannot apply to a column the model does not declare anyway.
     */
    protected function modelExposesAttribute(Model $model, string $key): bool
    {
        if (method_exists($model, 'hasAttribute')) {
            return $model->hasAttribute($key);
        }

        return array_key_exists($key, $model->getAttributes())
            || $model->hasCast($key)
            || $model->hasGetMutator($key)
            || $model->hasAttributeMutator($key);
    }
}
