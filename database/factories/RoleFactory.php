<?php

namespace CmsOrbit\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use CmsOrbit\Core\Settings\Models\Role;

class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $role = ['Admin', 'User'];
        $roles = [
            $role[0] => [
                'settings.index'              => 1,
                'settings.systems'            => 1,
                'settings.systems.roles'      => 1,
                'settings.systems.settings'   => 1,
                'settings.systems.users'      => 1,
                'settings.systems.attachment' => 1,
                'settings.systems.media'      => 1,
            ],
            $role[1] => [
                'settings.index'              => 1,
                'settings.systems'            => 1,
                'settings.systems.settings'   => 1,
                'settings.systems.comment'    => 1,
                'settings.systems.attachment' => 1,
                'settings.systems.media'      => 1,
            ],
        ];

        $selRole = $this->faker->randomElement($role);

        return [
            'name'        => $this->faker->lexify($selRole.'_???'),
            'slug'        => $this->faker->unique()->jobTitle,
            'permissions' => $roles[$selRole],
        ];
    }
}
