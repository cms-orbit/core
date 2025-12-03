<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Settings\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use CmsOrbit\Core\Settings\Models\User;

/**
 * This event is triggered when a role is added to a user.
 */
class AddRoleEvent
{
    use SerializesModels;

    /**
     * The role(s) that were added to the user.
     *
     * @var Collection
     */
    public $roles;

    /**
     * Create a new event instance.
     *
     * @param mixed $user The user to whom the role(s) is added
     * @param mixed $role The role(s) to be added
     */
    public function __construct(public mixed $user, mixed $role)
    {
        $this->roles = collect($role);
    }
}
