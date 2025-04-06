<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing locations
        Location::query()->delete();
        
        // Get all companies
        $companies = Company::all();
        
        foreach ($companies as $company) {
            // Add headquarters for all companies
            Location::create([
                'companyID' => $company->companyID,
                'company_address' => $this->getRandomAddress(),
                'location_type' => 'headquarters'
            ]);
            
            // Add type-specific locations
            switch ($company->company_type) {
                case 'broiler':
                    // Add 2 supplier locations for broiler companies
                    for ($i = 1; $i <= 2; $i++) {
                        Location::create([
                            'companyID' => $company->companyID,
                            'company_address' => $this->getRandomAddress(),
                            'location_type' => 'supplier'
                        ]);
                    }
                    break;
                    
                case 'slaughterhouse':
                    // Add 1 slaughterhouse location
                    Location::create([
                        'companyID' => $company->companyID,
                        'company_address' => $this->getRandomAddress(),
                        'location_type' => 'slaughterhouse'
                    ]);
                    break;
                    
                case 'sme':
                    // Add 1 kitchen location for SME companies
                    Location::create([
                        'companyID' => $company->companyID,
                        'company_address' => $this->getRandomAddress(),
                        'location_type' => 'kitchen'
                    ]);
                    break;
            }
        }
    }
    
    /**
     * Generate a random Malaysian address
     */
    private function getRandomAddress(): string
    {
        $streets = [
            'Jalan Bukit Bintang', 'Jalan Sultan Ismail', 'Jalan Ampang', 
            'Jalan Tun Razak', 'Jalan Imbi', 'Jalan Pudu', 'Jalan Masjid India',
            'Jalan Petaling', 'Jalan Raja Chulan', 'Jalan P. Ramlee'
        ];
        
        $cities = [
            'Kuala Lumpur', 'Petaling Jaya', 'Shah Alam', 'Subang Jaya', 
            'Johor Bahru', 'Penang', 'Ipoh', 'Melaka', 'Kota Kinabalu', 'Kuching'
        ];
        
        $states = [
            'Selangor', 'Kuala Lumpur', 'Johor', 'Penang', 
            'Perak', 'Melaka', 'Sabah', 'Sarawak'
        ];
        
        $street = $streets[array_rand($streets)];
        $number = rand(1, 200);
        $city = $cities[array_rand($cities)];
        $state = $states[array_rand($states)];
        $postcode = rand(10000, 99999);
        
        return "$number, $street, $postcode $city, $state, Malaysia";
    }
}