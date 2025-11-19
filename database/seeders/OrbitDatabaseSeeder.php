<?php

namespace CmsOrbit\Core\Database\Seeders;

use Illuminate\Database\Seeder;

class OrbitDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * php artisan db:seed --class="Orbit\Database\Seeds\OrbitDatabaseSeeder"
     *
     * run another class
     * php artisan db:seed --class="Orbit\Database\Seeds\UsersTableSeeder"
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            // AttachmentsTableSeeder::class,
            // UsersTableSeeder::class,
            // RolesTableSeeder::class,
            // SettingsTableSeeder::class,
        ]);
    }
}
