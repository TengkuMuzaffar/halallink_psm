<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class VideoDemoSeeder extends Seeder
{
    /**
     * Run the database seeds for video demonstration.
     * This seeder only creates admin and logistics companies.
     */
    public function run(): void
    {
        // Clear existing data
        User::query()->delete();
        Company::query()->delete();

        // 1. Admin | admin - 1 account
        $adminCompany = Company::create([
            'company_name' => 'White Space Resources',
            'company_type' => 'admin',
            'company_image' => 'companies/admin-logo.png',
        ]);

        // Admin user with no fullname
        User::create([
            'email' => 'admin@halallinkpsm.com',
            'password' => Hash::make('password123'),
            'companyID' => $adminCompany->companyID,
            'role' => 'admin',
            'status' => 'active',
            'tel_number' => '60123456789',
            'email_verified_at' => Carbon::now(),
            'image' => 'users/default.png',
        ]);

        // Create 3 employees for admin (reduced from 10 for demo purposes)
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'fullname' => "Admin Employee $i",
                'email' => "admin.employee$i@halallinkpsm.com",
                'password' => Hash::make('password123'),
                'companyID' => $adminCompany->companyID,
                'role' => 'employee',
                'status' => 'active',
                'tel_number' => "601234567" . str_pad($i, 2, '0', STR_PAD_LEFT),
                'email_verified_at' => Carbon::now(),
                'image' => 'users/default.png',
            ]);
        }

        // 2. Logistics - 3 accounts (reduced from 10 for demo purposes)
        for ($i = 1; $i <= 3; $i++) {
            $registrationDate = Carbon::now(); // Use current date for demo
            
            $logisticsCompany = Company::create([
                'company_name' => "Logistics Company $i",
                'company_type' => 'logistic',
                'company_image' => "companies/logistics-$i.png",
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
            ]);

            // Admin user for logistics
            User::create([
                'email' => "logistics.admin$i@halallinkpsm.com",
                'password' => Hash::make('password123'),
                'companyID' => $logisticsCompany->companyID,
                'role' => 'admin',
                'status' => 'active',
                'tel_number' => "601234567" . (80 + $i),
                'email_verified_at' => $registrationDate,
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
                'image' => 'users/default.png',
            ]);

            // Create 3 employees for each logistics company (reduced from 10 for demo purposes)
            for ($j = 1; $j <= 3; $j++) {
                User::create([
                    'fullname' => "Logistics $i Employee $j",
                    'email' => "logistics$i.employee$j@halallinkpsm.com",
                    'password' => Hash::make('password123'),
                    'companyID' => $logisticsCompany->companyID,
                    'role' => 'employee',
                    'status' => 'active',
                    'tel_number' => "6012348" . $i . str_pad($j, 2, '0', STR_PAD_LEFT),
                    'email_verified_at' => $registrationDate,
                    'created_at' => $registrationDate,
                    'updated_at' => $registrationDate,
                    'image' => 'users/default.png',
                ]);
            }
        }
    }
}