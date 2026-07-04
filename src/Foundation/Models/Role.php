<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Models;

use CmsOrbit\Core\Access\RoleAccess;
use CmsOrbit\Core\Filters\Filterable;
use CmsOrbit\Core\Filters\Types\Like;
use CmsOrbit\Core\Metrics\Chartable;
use CmsOrbit\Core\Screen\AsSource;
use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Role extends Model
{
    use AsSource, Chartable, Filterable, HasFactory, HasUuids, RoleAccess;

    public const SystemKeySuperAdmin = 'super-admin';
    public const DisplayNameSuperAdmin = '최고 관리자';

    /**
     * @var string
     */
    protected $table = 'roles';

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'system_key',
        'is_deletable',
        'permissions',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'is_deletable' => 'bool',
        'permissions'  => 'array',
    ];

    /**
     * @var array
     */
    protected $allowedFilters = [
        'name'        => Like::class,
        'slug'        => Like::class,
        'permissions' => Like::class,
    ];

    /**
     * @var array
     */
    protected $allowedSorts = [
        'name',
        'slug',
        'updated_at',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $role): void {
            if (blank($role->getAttribute('slug'))) {
                $role->setAttribute('slug', $role->isSuperAdmin()
                    ? self::SystemKeySuperAdmin
                    : Str::slug((string) $role->getAttribute('name')));
            }

            if ($role->isSuperAdmin()) {
                $role->forceFill([
                    'slug'         => self::SystemKeySuperAdmin,
                    'system_key'   => self::SystemKeySuperAdmin,
                    'is_deletable' => false,
                    'permissions'  => Orbit::getAllowAllPermission()->toArray(),
                ]);
            }
        });
    }

    public function isSuperAdmin(): bool
    {
        return $this->getAttribute('system_key') === self::SystemKeySuperAdmin
            || $this->getAttribute('slug') === self::SystemKeySuperAdmin;
    }

    public function isProtected(): bool
    {
        if ($this->isSuperAdmin() || ! $this->getAttribute('is_deletable')) {
            return true;
        }

        return filled($this->getAttribute('system_key'));
    }
}
