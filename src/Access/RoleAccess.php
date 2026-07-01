<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Access;

use CmsOrbit\Core\Foundation\Models\User;
use CmsOrbit\Core\Support\Facades\Orbit;
use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

trait RoleAccess
{
    use StatusAccess;

    /**
     * Define the relationship with the users assigned to the role
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(Orbit::model(User::class), 'role_users', 'role_id', 'user_id');
    }

    /**
     * Get the number of permissions assigned to the role
     */
    public function getCountPermissions(): int
    {
        return collect($this->permissions)->filter(fn (int $value) => $value)->count();
    }

    /**
     * Override the deleted method to detach the users assigned to the role
     * before deleting the role
     *
     * @throws Exception
     */
    public function delete(): ?bool
    {
        $isSoftDeleted = array_key_exists(SoftDeletes::class, class_uses($this));

        if ($this->exists && ! $isSoftDeleted) {
            $this->users()->detach();
        }

        return parent::delete();
    }
}
