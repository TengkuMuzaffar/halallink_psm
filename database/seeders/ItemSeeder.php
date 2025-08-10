<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Location;
use App\Models\Poultry;
use App\Models\User;
use App\Models\Item;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing items
        Item::query()->delete();
        
        // Get all poultry types
        $poultryTypes = Poultry::all();
        
        // Get all slaughterhouse locations from slaughterhouse companies
        $slaughterhouseLocations = Location::whereHas('company', function($query) {
            $query->where('company_type', 'slaughterhouse');
        })->where('location_type', 'slaughterhouse')->get();
        
        if ($slaughterhouseLocations->isEmpty()) {
            return; // Exit if no slaughterhouse locations available
        }
        
        // Get all broiler companies
        $broilerCompanies = Company::where('company_type', 'broiler')->get();
        
        foreach ($broilerCompanies as $company) {
            // Get company's main location (supplier location)
            $companyLocation = Location::where('companyID', $company->companyID)
                ->where('location_type', 'supplier')
                ->first();
        
            if (!$companyLocation) {
                continue; // Skip if no main location found
            }
        
            // Get admin and employees for this company
            $admin = User::where('companyID', $company->companyID)
                ->where('role', 'admin')
                ->first();
                
            $employees = User::where('companyID', $company->companyID)
                ->where('role', 'employee')
                ->get();
                
            if (!$admin || $employees->isEmpty()) {
                continue;
            }
            
            // Randomly select 2-3 slaughterhouse locations
            $selectedSlaughterhouses = $slaughterhouseLocations->random(min(rand(2, 3), $slaughterhouseLocations->count()));
            
            foreach ($selectedSlaughterhouses as $slaughterhouse) {
                foreach ($poultryTypes as $poultry) {
                    $itemCount = rand(2, 4);
                    
                    for ($i = 0; $i < $itemCount; $i++) {
                        $measurementType = 'kg';
                        $measurementValue = $this->getRandomMeasurementValue($measurementType);
                        $price = $this->getRandomPrice($poultry->poultry_name, $measurementType, $measurementValue);
                        $stock = rand(20, 200);
                        
                        $creator = rand(0, 10) < 3 ? $admin : $employees->random();
                        
                        Item::create([
                            'poultryID' => $poultry->poultryID,
                            'userID' => $creator->userID,
                            'locationID' => $companyLocation->locationID,  // Company's main location
                            'slaughterhouse_locationID' => $slaughterhouse->locationID,  // Slaughterhouse location
                            'measurement_type' => $measurementType,
                            // 'item_image' => "items/{$poultry->poultry_name}-{$measurementType}.png",
                            'measurement_value' => $measurementValue,
                            'price' => $price,
                            'stock' => $stock
                        ]);
                    }
                }
            }
        }
    }
    
    /**
     * Get a random measurement type
     */
    private function getRandomMeasurementType(): string
    {
        $types = ['kg', 'g', 'pound', 'oz', 'unit'];
        return $types[array_rand($types)];
    }
    
    /**
     * Get a random measurement value based on type
     */
    private function getRandomMeasurementValue(string $type): float
    {
        switch ($type) {
            case 'kg':
                return rand(1, 10) + (rand(0, 9) / 10);
            case 'g':
                return rand(100, 900);
            case 'pound':
                return rand(1, 20) + (rand(0, 9) / 10);
            case 'oz':
                return rand(4, 32) + (rand(0, 9) / 10);
            case 'unit':
                return rand(1, 10);
            default:
                return 1.0;
        }
    }
    
    /**
     * Get a random price based on poultry type and measurement
     */
    private function getRandomPrice(string $poultryName, string $measurementType, float $measurementValue): float
    {
        $basePrice = 0;
        
        // Set base price by poultry type
        switch ($poultryName) {
            case 'Chicken':
                $basePrice = 15;
                break;
            case 'Duck':
                $basePrice = 25;
                break;
            case 'Cow':
                $basePrice = 50;
                break;
            case 'Goat':
                $basePrice = 40;
                break;
            case 'Lamb':
                $basePrice = 45;
                break;
            case 'Turkey':
                $basePrice = 30;
                break;
            default:
                $basePrice = 10;
        }
        
        // Adjust by measurement type
        switch ($measurementType) {
            case 'kg':
                return round($basePrice * $measurementValue * (1 + (rand(-10, 10) / 100)), 2);
            case 'g':
                return round($basePrice * ($measurementValue / 1000) * (1 + (rand(-10, 10) / 100)), 2);
            case 'pound':
                return round($basePrice * ($measurementValue * 0.45359237) * (1 + (rand(-10, 10) / 100)), 2);
            case 'oz':
                return round($basePrice * ($measurementValue * 0.0283495) * (1 + (rand(-10, 10) / 100)), 2);
            case 'unit':
                return round($basePrice * $measurementValue * (1 + (rand(-10, 10) / 100)), 2);
            default:
                return round($basePrice * (1 + (rand(-10, 10) / 100)), 2);
        }
    }
}