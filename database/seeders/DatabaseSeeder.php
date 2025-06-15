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
            VideoDemoSeeder::class,  // Use the video demo seeder instead of UserCompanySeeder
            // LocationSeeder::class,
            // PoultrySeeder::class,
            // ItemSeeder::class,
            // VehicleSeeder::class,
            // SmeOrderSeeder::class,
            // CheckpointCreatorSeeder::class,
            // OrderCheckpointSeeder::class,
        ]);
    }
}
