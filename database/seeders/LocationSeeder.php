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
                    // Add 5 supplier locations for broiler companies (increased from 2)
                    for ($i = 1; $i <= 5; $i++) {
                        Location::create([
                            'companyID' => $company->companyID,
                            'company_address' => $this->getRandomAddress(),
                            'location_type' => 'supplier'
                        ]);
                    }
                    break;
                    
                case 'slaughterhouse':
                    // Add 3 slaughterhouse locations (increased from 1)
                    for ($i = 1; $i <= 3; $i++) {
                        Location::create([
                            'companyID' => $company->companyID,
                            'company_address' => $this->getRandomAddress(),
                            'location_type' => 'slaughterhouse'
                        ]);
                    }
                    break;
                    
                case 'sme':
                    // Add 3 kitchen locations for SME companies (increased from 1)
                    for ($i = 1; $i <= 3; $i++) {
                        Location::create([
                            'companyID' => $company->companyID,
                            'company_address' => $this->getRandomAddress(),
                            'location_type' => 'kitchen'
                        ]);
                    }
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
            'Jalan Petaling', 'Jalan Raja Chulan', 'Jalan P. Ramlee',
            'Jalan Alor', 'Jalan Hang Tuah', 'Jalan Bukit Aman', 'Jalan Travers',
            'Jalan Damansara', 'Jalan Kuching', 'Jalan Ipoh', 'Jalan Klang Lama',
            'Jalan Cheras', 'Jalan Genting Klang'
        ];
        
        $cities = [
            'Kuala Lumpur', 'Petaling Jaya', 'Shah Alam', 'Subang Jaya', 
            'Johor Bahru', 'Penang', 'Ipoh', 'Melaka', 'Kota Kinabalu', 'Kuching',
            'Seremban', 'Alor Setar', 'Kuantan', 'Kota Bharu', 'Kuala Terengganu'
        ];
        
        $states = [
            'Selangor', 'Kuala Lumpur', 'Johor', 'Penang', 
            'Perak', 'Melaka', 'Sabah', 'Sarawak', 'Negeri Sembilan',
            'Kedah', 'Pahang', 'Kelantan', 'Terengganu'
        ];
        
        $street = $streets[array_rand($streets)];
        $number = rand(1, 200);
        $city = $cities[array_rand($cities)];
        $state = $states[array_rand($states)];
        $postcode = rand(10000, 99999);
        
        return "$number, $street, $postcode $city, $state, Malaysia";
    }
}