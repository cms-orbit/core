<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Configuration;

use CmsOrbit\Core\Foundation\Entity\Entity;
use CmsOrbit\Core\Foundation\Entity\EntityRegistry;
use Illuminate\Support\Collection;

trait ManagesEntities
{
    /**
     * Submit Entity descriptors to Core. Accepts a directory to scan, a single
     * Entity class, or an array of paths/classes. This is the primary package
     * contract used by external packages in their ServiceProvider::boot().
     *
     * @param  string|array<int, string>  $pathOrClass
     */
    public function registerEntities(string|array $pathOrClass): static
    {
        app(EntityRegistry::class)->register($pathOrClass);

        return $this;
    }

    /**
     * All resolved entities keyed by uriKey.
     *
     * @return Collection<string, Entity>
     */
    public function getEntities(): Collection
    {
        return app(EntityRegistry::class)->all();
    }

    /**
     * Resolve a single entity by its uriKey.
     */
    public function getEntity(string $uriKey): ?Entity
    {
        return app(EntityRegistry::class)->find($uriKey);
    }
}
