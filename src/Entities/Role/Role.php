<?php

namespace CmsOrbit\Core\Entities\Role;

use CmsOrbit\Core\Auth\Models\Role as BaseRole;
use CmsOrbit\Core\Models\Concerns\HasPermissions;

class Role extends BaseRole
{
    use HasPermissions;

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'roles';
}
