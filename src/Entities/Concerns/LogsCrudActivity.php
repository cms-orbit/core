<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Entities\Concerns;

use CmsOrbit\Core\Activity\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait LogsCrudActivity
{
    /**
     * @param callable(): void $persist
     * @param string[]         $ignored
     */
    protected function persistWithActivity(Request $request, Model $model, callable $persist, array $ignored = []): void
    {
        $exists = $model->exists;
        $original = $exists ? $model->getOriginal() : [];

        $persist();

        $logger = app(ActivityLogger::class);

        if (! $exists) {
            $logger->logModelCreated($model);

            return;
        }

        $logger->logModelUpdated($model, $model->getChanges(), $original, $ignored);
    }

    /**
     * @param callable(): void $delete
     */
    protected function deleteWithActivity(Model $model, callable $delete): void
    {
        $snapshot = clone $model;

        $delete();

        app(ActivityLogger::class)->logModelDeleted($snapshot);
    }
}
