<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Access;

use CmsOrbit\Core\Foundation\Events\AddRoleEvent;
use CmsOrbit\Core\Foundation\Events\RemoveRoleEvent;
use CmsOrbit\Core\Foundation\Models\Role;
use CmsOrbit\Core\Support\Facades\Orbit;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait UserAccess
{
    use StatusAccess;

    /**
     * @var null|Collection
     */
    private $cachePermissions;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Orbit::model(Role::class), 'role_users', 'user_id', 'role_id');
    }

    /**
     * @param Role|int|string $role
     */
    public function inRole($role): bool
    {
        if ($role instanceof Model) {
            return $this->roles()->whereKey($role->getKey())->exists();
        }

        if (! is_string($role) && ! is_int($role)) {
            return false;
        }

        return $this->roles()
            ->where(function (Builder $builder) use ($role) {
                $builder->whereKey($role);

                if (is_string($role)) {
                    $builder->orWhere('name', $role);
                }
            })
            ->exists();
    }

    public function hasAccess(string $permit, bool $cache = true): bool
    {
        return $this->effectivePermissions($cache)
            ->filter(fn (array $permissions) => $this->filterWildcardAccess($permissions, $permit))
            ->isNotEmpty();
    }

    /**
     * Permissions can be checked based on wildcards
     * using the * character to match any of a set of permissions.
     */
    protected function filterWildcardAccess(array $permissions, string $permit): bool
    {
        return collect($permissions)
            ->filter(fn (bool $value, $permission) => Str::is($permit, $permission) && $value)
            ->isNotEmpty();
    }

    /**
     * This method will grant access if any permission passes the check.
     *
     * @param string|iterable $permissions
     */
    public function hasAnyAccess($permissions, bool $cache = true): bool
    {
        if (empty($permissions)) {
            return true;
        }

        return collect($permissions)
            ->map(fn (string $permit) => $this->hasAccess($permit, $cache))
            ->filter(fn (bool $result) => $result === true)
            ->isNotEmpty();
    }

    /**
     * Query Scope for retrieving users by a certain permission
     * The * character usage is not implemented.
     */
    public function scopeByAccess(Builder $builder, string $permitWithoutWildcard): Builder
    {
        if (empty($permitWithoutWildcard)) {
            return $builder->whereRaw('1=0');
        }

        return $this->scopeByAnyAccess($builder, $permitWithoutWildcard);
    }

    /**
     * Query Scope for retrieving users by any permissions
     * The * character usage is not implemented.
     *
     * @param string|iterable $permitsWithoutWildcard
     */
    public function scopeByAnyAccess(Builder $builder, $permitsWithoutWildcard): Builder
    {
        $permits = collect($permitsWithoutWildcard);

        if ($permits->isEmpty()) {
            return $builder->whereRaw('1=0');
        }

        return $builder
            ->where(function (Builder $builder) use ($permits) {
                $permits->each(function ($permit) use ($builder) {
                    $builder
                        ->orWhere('permissions->'.$permit, true)
                        ->orWhere(function (Builder $builder) use ($permit) {
                            $builder
                                ->where(function (Builder $builder) use ($permit) {
                                    $builder
                                        ->whereNull('permissions->'.$permit)
                                        ->orWhere('permissions->'.$permit, true);
                                })
                                ->whereHas('roles', function (Builder $builder) use ($permit) {
                                    $builder->where('permissions->'.$permit, true);
                                });
                        });
                });
            });
    }

    /**
     * Direct permissions override inherited role permissions by slug, allowing a
     * user-specific deny (`false`) to cancel a role grant.
     *
     * @return Collection<int, array<string, bool>>
     */
    protected function effectivePermissions(bool $cache = true): Collection
    {
        if (! $cache || $this->cachePermissions === null) {
            $rolePermissions = $this->roles()
                ->pluck('permissions')
                ->filter(fn ($permission) => is_array($permission))
                ->reduce(
                    fn (Collection $permissions, array $items) => $permissions->merge($this->normalizePermissionMap($items)),
                    collect()
                );

            $directPermissions = $this->normalizePermissionMap($this->permissions);

            $this->cachePermissions = $rolePermissions
                ->merge($directPermissions)
                ->map(fn (bool $enabled, string $slug) => [$slug => $enabled])
                ->values();
        }

        return $this->cachePermissions;
    }

    /**
     * @param mixed $permissions
     *
     * @return Collection<string, bool>
     */
    protected function normalizePermissionMap($permissions): Collection
    {
        if (! is_array($permissions)) {
            return collect();
        }

        return collect($permissions)
            ->mapWithKeys(fn ($enabled, $slug) => [(string) $slug => (bool) $enabled]);
    }

    public function addRole(Model $role): Model
    {
        $result = $this->roles()->save($role);

        $this->eventAddRole($role);
        $this->clearCachePermission();

        return $result;
    }

    /**
     * @return $this
     */
    public function removeRole(Model $role)
    {
        $this->roles()->detach($role->getKey());

        $this->eventRemoveRole($role);
        $this->clearCachePermission();

        return $this;
    }

    /**
     * @return $this
     */
    public function removeRoleBySlug(string $slug)
    {
        $role = $this->roles()
            ->where('name', $slug)
            ->first();

        if ($role === null) {
            return $this;
        }

        return $this->removeRole($role);
    }

    /**
     * @return $this
     */
    public function replaceRoles(?array $roles = [])
    {
        $this->roles()->detach();

        $this->eventRemoveRole($roles);

        $this->roles()->attach($roles);

        $this->eventAddRole($roles);
        $this->clearCachePermission();

        return $this;
    }

    /**
     * @param Model|RoleInterface|RoleInterface[] $roles
     */
    public function eventAddRole($roles)
    {
        event(new AddRoleEvent($this, $roles));
    }

    /**
     * @param Model|RoleInterface|RoleInterface[] $roles
     */
    public function eventRemoveRole($roles)
    {
        event(new RemoveRoleEvent($this, $roles));
    }

    /**
     * @throws Exception
     */
    public function delete(): bool
    {
        $isSoftDeleted = array_key_exists(SoftDeletes::class, class_uses($this));

        if ($this->exists && ! $isSoftDeleted) {
            $this->roles()->detach();
        }

        return parent::delete();
    }

    public function clearCachePermission(): self
    {
        $this->cachePermissions = null;

        return $this;
    }
}
