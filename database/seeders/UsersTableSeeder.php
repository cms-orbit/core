<?php

namespace CmsOrbit\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use CmsOrbit\Core\Settings\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(User::class, 10)->create();
    }
}
