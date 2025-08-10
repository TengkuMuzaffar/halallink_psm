<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Poultry;

class PoultrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing poultry types
        Poultry::query()->delete();
        
        // Create poultry types (expanded list)
        $poultryTypes = [
            [
                'poultry_name' => 'Chicken',
            ],
            [
                'poultry_name' => 'Duck',
            ],
            [
                'poultry_name' => 'Cow',
            ],
            [
                'poultry_name' => 'Goat',
            ],
            [
                'poultry_name' => 'Lamb',
            ],
            [
                'poultry_name' => 'Turkey',
            ]
        ];
        
        foreach ($poultryTypes as $poultryType) {
            Poultry::create($poultryType);
        }
    }
}