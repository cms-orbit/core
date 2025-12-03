<?php

namespace CmsOrbit\Core\Entities\User;

use CmsOrbit\Core\Auth\Access\UserAccess;
use CmsOrbit\Core\Auth\Models\User as BaseUser;
use CmsOrbit\Core\Entities\User\Factories\UserFactory;
use CmsOrbit\Core\Models\Concerns\HasPermissions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends BaseUser
{
    use SoftDeletes, Notifiable, TwoFactorAuthenticatable;
    use HasPermissions, UserAccess;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model
     *
     * @return Factory<static>
     */
    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    /**
     * Get custom permissions
     */
    public static function getPermissions(): array
    {
        return [
            ...self::getDefaultPermissions(),
            new \CmsOrbit\Core\Auth\Permission(
                'orbit.entities.users.impersonate',
                __('Impersonate'),
                __('Users')
            ),
        ];
    }
}
