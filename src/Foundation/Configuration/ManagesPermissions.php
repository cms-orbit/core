<?php

namespace CmsOrbit\Core\Foundation\Configuration;

use CmsOrbit\Core\Foundation\ItemPermission;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait ManagesPermissions
{
    /**
     * A registry of all registered permissions, grouped by category.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $registeredPermissions = [];

    /**
     * A list of revoked permission slugs.
     *
     * @var string[]
     */
    protected array $removedPermissions = [];

    /**
     * @deprecated Use registerPermission instead.
     */
    public function registerPermissions(ItemPermission $permission): static
    {
        return $this->registerPermission($permission);
    }

    /**
     * Registers a ItemPermission that defines authentication permissions.
     */
    public function registerPermission(ItemPermission $permission): static
    {
        $old = Arr::get($this->registeredPermissions, $permission->group, []);

        $this->registeredPermissions[$permission->group] = array_merge($old, $permission->items);

        return $this;
    }

    /**
     * Retrieve permissions based on specified groups.
     */
    public function getPermission(string|array $groups = []): Collection
    {
        return collect($this->registeredPermissions)
            ->when(! empty($groups), fn (Collection $collection) => $collection->only($groups))
            ->map(fn ($group) => collect($group)->whereNotIn('slug', $this->removedPermissions));
    }

    /**
     * Get all registered permissions with the enabled state.
     */
    public function getAllowAllPermission(string|array $groups = []): Collection
    {
        return $this->getPermission($groups)
            ->collapse()
            ->reduce(static fn (Collection $permissions, array $item) => $permissions->put($item['slug'], true), collect());
    }

    /**
     * Remove a specific permission by key.
     */
    public function removePermission(string $key): static
    {
        $this->removedPermissions[] = $key;

        return $this;
    }
}
