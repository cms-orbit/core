<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use CmsOrbit\Core\Access\RoleAccess;
use CmsOrbit\Core\Access\RoleInterface;
use CmsOrbit\Core\Filters\Filterable;
use CmsOrbit\Core\Filters\Types\Like;
use CmsOrbit\Core\Filters\Types\Where;
use CmsOrbit\Core\Metrics\Chartable;
use CmsOrbit\Core\Screen\AsSource;

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
