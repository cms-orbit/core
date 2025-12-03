<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use CmsOrbit\Core\Auth\Access\RoleAccess;
use CmsOrbit\Core\UI\AsSource;

class Role extends Model
{
    use AsSource, HasFactory, RoleAccess;

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

}
