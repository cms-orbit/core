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
                'orbit.index'                      => 1,
                'orbit.systems'                    => 1,
                'orbit.systems.index'              => 1,
                'orbit.systems.roles'              => 1,
                'orbit.systems.settings'           => 1,
                'orbit.systems.users'              => 1,
                'orbit.systems.comment'            => 1,
                'orbit.systems.attachment'         => 1,
                'orbit.systems.media'              => 1,
            ],
            'user'   => [
                'orbit.index'                       => 1,
                'orbit.systems'                     => 1,
                'orbit.systems.roles'               => 0,
                'orbit.systems.settings'            => 1,
                'orbit.systems.users'               => 0,
                'orbit.systems.menu'                => 0,
                'orbit.systems.attachment'          => 1,
                'orbit.systems.media'               => 1,
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
