<?php

namespace CmsOrbit\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use CmsOrbit\Core\Settings\Models\Role;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Role::class, 5)->create();
    }
}
