<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use CmsOrbit\Core\Auth\Access\RoleAccess;
use CmsOrbit\Core\Auth\Access\RoleInterface;
use CmsOrbit\Core\Foundation\Filters\Filterable;
use CmsOrbit\Core\Foundation\Filters\Types\Like;
use CmsOrbit\Core\Foundation\Filters\Types\Where;
use CmsOrbit\Core\Foundation\Metrics\Chartable;
use CmsOrbit\Core\UI\AsSource;

class Role extends Model implements RoleInterface
{
    use AsSource, Chartable, Filterable, HasFactory, RoleAccess;

    /**
     * @var string
     */
    protected $table = 'roles';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'slug',
        'permissions',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * @var array
     */
    protected $allowedFilters = [
        'id'          => Where::class,
        'name'        => Like::class,
        'slug'        => Like::class,
        'permissions' => Like::class,
    ];

    /**
     * @var array
     */
    protected $allowedSorts = [
        'id',
        'name',
        'slug',
        'updated_at',
        'created_at',
    ];
}
