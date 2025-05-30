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
                'poultry_image' => 'poultry/chicken.png'
            ],
            [
                'poultry_name' => 'Duck',
                'poultry_image' => 'poultry/duck.png'
            ],
            [
                'poultry_name' => 'Cow',
                'poultry_image' => 'poultry/cow.png'
            ],
            [
                'poultry_name' => 'Goat',
                'poultry_image' => 'poultry/goat.png'
            ],
            [
                'poultry_name' => 'Lamb',
                'poultry_image' => 'poultry/lamb.png'
            ],
            [
                'poultry_name' => 'Turkey',
                'poultry_image' => 'poultry/turkey.png'
            ]
        ];
        
        foreach ($poultryTypes as $poultryType) {
            Poultry::create($poultryType);
        }
    }
}