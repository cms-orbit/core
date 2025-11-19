<?php

namespace CmsOrbit\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use CmsOrbit\Core\Settings\Models\User;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $roles = [
            'admin'  => [
                'settings.index'                      => 1,
                'settings.systems'                    => 1,
                'settings.systems.index'              => 1,
                'settings.systems.roles'              => 1,
                'settings.systems.settings'           => 1,
                'settings.systems.users'              => 1,
                'settings.systems.comment'            => 1,
                'settings.systems.attachment'         => 1,
                'settings.systems.media'              => 1,
            ],
            'user'   => [
                'settings.index'                       => 1,
                'settings.systems'                     => 1,
                'settings.systems.roles'               => 0,
                'settings.systems.settings'            => 1,
                'settings.systems.users'               => 0,
                'settings.systems.menu'                => 0,
                'settings.systems.attachment'          => 1,
                'settings.systems.media'               => 1,
            ],
        ];

        return [
            'name'           => $this->faker->firstName,
            'email'          => $this->faker->unique()->safeEmail,
            'password'       => Hash::make('password'),
            'remember_token' => Str::random(10),
            'permissions'    => $roles['admin'],
        ];
    }
}
