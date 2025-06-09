<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
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

        // Admin user with no fullname (already correct)
        User::create([
            'email' => 'admin@halallink.com',
            'password' => Hash::make('password123'),
            'companyID' => $adminCompany->companyID,
            'role' => 'admin',
            'status' => 'active',
            'tel_number' => '60123456789',
            'email_verified_at' => Carbon::now(),
            'image' => 'users/default.png',
        ]);

        // Create 10 employees for each company type
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'fullname' => "Admin Employee $i",
                'email' => "admin.employee$i@halallink.com",
                'password' => Hash::make('password123'),
                'companyID' => $adminCompany->companyID,
                'role' => 'employee',
                'status' => 'active',
                'tel_number' => "601234567" . str_pad($i, 2, '0', STR_PAD_LEFT),
                'email_verified_at' => Carbon::now(),
                'image' => 'users/default.png',
            ]);
        }

        // Calculate date range for the past year
        $startDate = Carbon::now()->subYear();
        $endDate = Carbon::now();
        $dateRange = $endDate->diffInDays($startDate);

        // Admin | logistics - 10 accounts
        for ($i = 1; $i <= 10; $i++) {
            // Calculate a random date within the past year
            $randomDaysAgo = rand(0, $dateRange);
            $registrationDate = Carbon::now()->subDays($randomDaysAgo);
            
            $logisticsCompany = Company::create([
                'company_name' => "Logistics Company $i",
                'company_type' => 'logistic',
                'company_image' => "companies/logistics-$i.png",
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
            ]);

            // Admin user with no fullname
            User::create([
                'email' => "logistics.admin$i@halallink.com",
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

            // Create 10 employees for logistics company
            for ($j = 1; $j <= 10; $j++) {
                User::create([
                    'fullname' => "Logistics $i Employee $j",
                    'email' => "logistics$i.employee$j@halallink.com",
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

        // Admin | broiler - 10 accounts
        for ($i = 1; $i <= 10; $i++) {
            // Calculate a random date within the past year
            $randomDaysAgo = rand(0, $dateRange);
            $registrationDate = Carbon::now()->subDays($randomDaysAgo);
            
            $broilerCompany = Company::create([
                'company_name' => "Broiler Company $i",
                'company_type' => 'broiler',
                'company_image' => "companies/broiler-$i.png",
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
            ]);

            // Admin user with no fullname
            User::create([
                'email' => "broiler.admin$i@halallink.com",
                'password' => Hash::make('password123'),
                'companyID' => $broilerCompany->companyID,
                'role' => 'admin',
                'status' => 'active',
                'tel_number' => "601234567" . (70 + $i),
                'email_verified_at' => $registrationDate,
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
                'image' => 'users/default.png',
            ]);

            // Create 10 employees for each broiler company
            for ($j = 1; $j <= 10; $j++) {
                User::create([
                    'fullname' => "Broiler $i Employee $j",
                    'email' => "broiler$i.employee$j@halallink.com",
                    'password' => Hash::make('password123'),
                    'companyID' => $broilerCompany->companyID,
                    'role' => 'employee',
                    'status' => 'active',
                    'tel_number' => "6012345" . $i . str_pad($j, 2, '0', STR_PAD_LEFT),
                    'email_verified_at' => $registrationDate,
                    'created_at' => $registrationDate,
                    'updated_at' => $registrationDate,
                    'image' => 'users/default.png',
                ]);
            }
        }

        // Admin | SME - 15 accounts
        for ($i = 1; $i <= 15; $i++) {
            // Calculate a random date within the past year
            $randomDaysAgo = rand(0, $dateRange);
            $registrationDate = Carbon::now()->subDays($randomDaysAgo);
            
            $smeCompany = Company::create([
                'company_name' => "SME Company $i",
                'company_type' => 'sme',
                'company_image' => "companies/sme-$i.png",
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
            ]);

            // Admin user with no fullname
            User::create([
                'email' => "sme.admin$i@halallink.com",
                'password' => Hash::make('password123'),
                'companyID' => $smeCompany->companyID,
                'role' => 'admin',
                'status' => 'active',
                'tel_number' => "601234567" . (50 + $i),
                'email_verified_at' => $registrationDate,
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
                'image' => 'users/default.png',
            ]);

            // Create 10 employees for each SME company
            for ($j = 1; $j <= 10; $j++) {
                User::create([
                    'fullname' => "SME $i Employee $j",
                    'email' => "sme$i.employee$j@halallink.com",
                    'password' => Hash::make('password123'),
                    'companyID' => $smeCompany->companyID,
                    'role' => 'employee',
                    'status' => 'active',
                    'tel_number' => "6012346" . $i . str_pad($j, 2, '0', STR_PAD_LEFT),
                    'email_verified_at' => $registrationDate,
                    'created_at' => $registrationDate,
                    'updated_at' => $registrationDate,
                    'image' => 'users/default.png',
                ]);
            }
        }

        // Admin | slaughterhouse - 1 account
        $slaughterhouseCompany = Company::create([
            'company_name' => 'Slaughterhouse Company',
            'company_type' => 'slaughterhouse',
            'company_image' => 'companies/slaughterhouse.png',
        ]);

        // Admin user with no fullname
        User::create([
            'email' => 'slaughterhouse.admin@halallink.com',
            'password' => Hash::make('password123'),
            'companyID' => $slaughterhouseCompany->companyID,
            'role' => 'admin',
            'status' => 'active',
            'tel_number' => '60123456730',
            'email_verified_at' => Carbon::now(),
            'image' => 'users/default.png',
        ]);

        // Create 10 employees for slaughterhouse company
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'fullname' => "Slaughterhouse Employee $i",
                'email' => "slaughterhouse.employee$i@halallink.com",
                'password' => Hash::make('password123'),
                'companyID' => $slaughterhouseCompany->companyID,
                'role' => 'employee',
                'status' => 'active',
                'tel_number' => "6012347" . str_pad($i, 2, '0', STR_PAD_LEFT),
                'email_verified_at' => Carbon::now(),
                'image' => 'users/default.png',
            ]);
        }
    }
}