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
            UserCompanySeeder::class,
            LocationSeeder::class,
            PoultrySeeder::class,
            ItemSeeder::class,
            VehicleSeeder::class,
            // OrderCheckpointSeeder::class, // Add the new OrderCheckpointSeeder
        ]);
    }
}
