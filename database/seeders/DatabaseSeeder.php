<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProvincesSeeder::class,
            CitiesSeeder::class,
            CategoriesSeeder::class,
            SubcategoriesSeeder::class,
            ProductClassesSeeder::class,
            ProductSubclassesSeeder::class,
            ClientAgentsSeeder::class,
            JobOrderAgentsSeeder::class,
            PaymentOptionsSeeder::class,
            RolesAndPermissionsSeeder::class,
        ]);
    }
}
