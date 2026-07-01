<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Models;

use CmsOrbit\Core\Access\RoleAccess;
use CmsOrbit\Core\Filters\Filterable;
use CmsOrbit\Core\Filters\Types\Like;
use CmsOrbit\Core\Metrics\Chartable;
use CmsOrbit\Core\Screen\AsSource;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use AsSource, Chartable, Filterable, HasFactory, HasUuids, RoleAccess;

    /**
     * @var string
     */
    protected $table = 'roles';

    /**
     * @var array
     */
    protected $fillable = [
        'name',
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
        'name' => Like::class,
        'permissions' => Like::class,
    ];

    /**
     * @var array
     */
    protected $allowedSorts = [
        'name',
        'updated_at',
        'created_at',
    ];
}
