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
            SmeOrderSeeder::class,        // Add the new SmeOrderSeeder
            CheckpointCreatorSeeder::class, // Add the new CheckpointCreatorSeeder
            // OrderCheckpointSeeder::class, // Comment out the old seeder
        ]);
    }
}
