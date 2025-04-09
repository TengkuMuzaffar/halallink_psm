<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Vehicle;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing vehicles
        Vehicle::query()->delete();
        
        // Get all logistics companies
        $logisticsCompanies = Company::where('company_type', 'logistic')->get();
        
        // Vehicle plate prefixes for Malaysian states
        $platePrefixes = [
            'W', 'V', 'B', 'A', 'K', 'P', 'J', 'M', 'N', 'T',
            'R', 'D', 'KV', 'SA', 'SB', 'QA', 'QK', 'QM', 'QL'
        ];
        
        foreach ($logisticsCompanies as $company) {
            // Create 20 vehicles for each logistics company
            for ($i = 1; $i <= 20; $i++) {
                // Generate a random Malaysian vehicle plate
                $platePrefix = $platePrefixes[array_rand($platePrefixes)];
                $plateNumber = rand(1, 9999);
                $plateSuffix = chr(rand(65, 90)) . chr(rand(65, 90)) . chr(rand(65, 90));
                $vehiclePlate = $platePrefix . ' ' . $plateNumber . ' ' . $plateSuffix;
                
                // Generate a variety of weights (in kg)
                // Small vehicles: 1000-3000 kg
                // Medium vehicles: 3001-8000 kg
                // Large vehicles: 8001-15000 kg
                $weightCategory = rand(1, 3);
                
                switch ($weightCategory) {
                    case 1: // Small vehicles
                        $weight = rand(1000, 3000);
                        break;
                    case 2: // Medium vehicles
                        $weight = rand(3001, 8000);
                        break;
                    case 3: // Large vehicles
                        $weight = rand(8001, 15000);
                        break;
                }
                
                // Create the vehicle
                Vehicle::create([
                    'companyID' => $company->companyID,
                    'vehicle_plate' => $vehiclePlate,
                    'vehicle_load_weight' => $weight
                ]);
            }
        }
        
        // Output message
        $this->command->info('Created ' . (20 * $logisticsCompanies->count()) . ' vehicles for ' . $logisticsCompanies->count() . ' logistics companies.');
    }
}