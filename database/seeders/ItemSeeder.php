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
        
        // Get all broiler companies
        $broilerCompanies = Company::where('company_type', 'broiler')->get();
        
        foreach ($broilerCompanies as $company) {
            // Get supplier locations for this company
            $supplierLocations = Location::where('companyID', $company->companyID)
                ->where('location_type', 'supplier')
                ->get();
                
            // Get admin user for this company
            $adminUser = User::where('companyID', $company->companyID)
                ->where('role', 'admin')
                ->first();
                
            if (!$adminUser || $supplierLocations->isEmpty()) {
                continue; // Skip if no admin or supplier locations
            }
            
            // Create items for each supplier location and poultry type
            foreach ($supplierLocations as $location) {
                foreach ($poultryTypes as $poultry) {
                    // Create 2-4 items per poultry type per location
                    $itemCount = rand(2, 4);
                    
                    for ($i = 0; $i < $itemCount; $i++) {
                        $measurementType = $this->getRandomMeasurementType();
                        $measurementValue = $this->getRandomMeasurementValue($measurementType);
                        $price = $this->getRandomPrice($poultry->poultry_name, $measurementType, $measurementValue);
                        $stock = rand(10, 100);
                        
                        Item::create([
                            'poultryID' => $poultry->poultryID,
                            'userID' => $adminUser->userID,
                            'locationID' => $location->locationID,
                            'measurement_type' => $measurementType,
                            'item_image' => "items/{$poultry->poultry_name}-{$measurementType}.png",
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
        $types = ['kg', 'g', 'pound'];
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
                // 1 pound ≈ 0.45359237 kg
                return round($basePrice * ($measurementValue * 0.45359237) * (1 + (rand(-10, 10) / 100)), 2);
            default:
                return $basePrice;
        }
    }
}