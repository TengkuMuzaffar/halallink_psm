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
        ]);

        // Admin user with no fullname (already correct)
        User::create([
            'email' => 'admin@halallink.com',
            'password' => Hash::make('123'),
            'companyID' => $adminCompany->companyID,
            'role' => 'admin',
            'status' => 'active',
            'tel_number' => '60123456789',
            'email_verified_at' => Carbon::now(),
        ]);

        // Create 2 employees for admin company
        for ($i = 1; $i <= 2; $i++) {
            User::create([
                'fullname' => "Admin Employee $i",
                'email' => "admin.employee$i@halallink.com",
                'password' => Hash::make('123'),
                'companyID' => $adminCompany->companyID,
                'role' => 'employee',
                'status' => 'active',
                'tel_number' => "601234567" . str_pad($i, 2, '0', STR_PAD_LEFT),
                'email_verified_at' => Carbon::now(),
            ]);
        }

        // Calculate date range for the past year
        $startDate = Carbon::now()->subYear();
        $endDate = Carbon::now();
        $dateRange = $endDate->diffInDays($startDate);

    // Admin | logistics - 5 accounts
    for ($i = 1; $i <= 5; $i++) {
            // Calculate a random date within the past year
            $randomDaysAgo = rand(0, $dateRange);
            $registrationDate = Carbon::now()->subDays($randomDaysAgo);
            
            $logisticsCompany = Company::create([
                'company_name' => "Logistics Company $i",
                'company_type' => 'logistic',
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
            ]);

            // Admin user with no fullname
            User::create([
                'email' => "logistics.admin$i@halallink.com",
                'password' => Hash::make('123'),
                'companyID' => $logisticsCompany->companyID,
                'role' => 'admin',
                'status' => 'active',
                'tel_number' => "601234567" . (80 + $i),
                'email_verified_at' => $registrationDate,
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
            ]);

            // Create 2 employees for logistics company
            for ($j = 1; $j <= 2; $j++) {
                User::create([
                    'fullname' => "Logistics $i Employee $j",
                    'email' => "logistics$i.employee$j@halallink.com",
                    'password' => Hash::make('123'),
                    'companyID' => $logisticsCompany->companyID,
                    'role' => 'employee',
                    'status' => 'active',
                    'tel_number' => "6012348" . $i . str_pad($j, 2, '0', STR_PAD_LEFT),
                    'email_verified_at' => $registrationDate,
                    'created_at' => $registrationDate,
                    'updated_at' => $registrationDate,
                ]);
            }
        }

    // Admin | broiler - 5 accounts
    for ($i = 1; $i <= 5; $i++) {
            // Calculate a random date within the past year
            $randomDaysAgo = rand(0, $dateRange);
            $registrationDate = Carbon::now()->subDays($randomDaysAgo);
            
            $broilerCompany = Company::create([
                'company_name' => "Broiler Company $i",
                'company_type' => 'broiler',
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
            ]);

            // Admin user with no fullname
            $adminEmail = match($i) {
                1 => "randomsolutionprog@gmail.com",
                2 => "haiqalm302@gmail.com",
                default => "broiler.admin$i@halallink.com",
            };
            User::create([
                'email' => $adminEmail,
                'password' => Hash::make('123'),
                'companyID' => $broilerCompany->companyID,
                'role' => 'admin',
                'status' => 'active',
                'tel_number' => "601234567" . (70 + $i),
                'email_verified_at' => $registrationDate,
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
            ]);

            // Create 2 employees for each broiler company
            $employeeData = match($i) {
                1 => [
                    ["Broiler 1 Employee 1", "yasinibrahim304@gmail.com"],
                    ["Broiler 1 Employee 2", "arifdaniell321@gmail.com"],
                ],
                2 => [
                    ["Broiler 2 Employee 1", "qallso4588@gmail.com"],
                    ["Broiler 2 Employee 2", "broiler2.employee2@halallink.com"],
                ],
                default => [
                    ["Broiler $i Employee 1", "broiler$i.employee1@halallink.com"],
                    ["Broiler $i Employee 2", "broiler$i.employee2@halallink.com"],
                ]
            };
            foreach ($employeeData as $j => [$fullname, $email]) {
                User::create([
                    'fullname' => $fullname,
                    'email' => $email,
                    'password' => Hash::make('123'),
                    'companyID' => $broilerCompany->companyID,
                    'role' => 'employee',
                    'status' => 'active',
                    'tel_number' => "6012345" . $i . str_pad($j+1, 2, '0', STR_PAD_LEFT),
                    'email_verified_at' => $registrationDate,
                    'created_at' => $registrationDate,
                    'updated_at' => $registrationDate,
                ]);
            }
        }

    // Admin | SME - 5 accounts
    for ($i = 1; $i <= 5; $i++) {
            // Calculate a random date within the past year
            $randomDaysAgo = rand(0, $dateRange);
            $registrationDate = Carbon::now()->subDays($randomDaysAgo);
            
            $smeCompany = Company::create([
                'company_name' => "SME Company $i",
                'company_type' => 'sme',
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
            ]);

            // Admin user with no fullname
            User::create([
                'email' => "sme.admin$i@halallink.com",
                'password' => Hash::make('123'),
                'companyID' => $smeCompany->companyID,
                'role' => 'admin',
                'status' => 'active',
                'tel_number' => "601234567" . (50 + $i),
                'email_verified_at' => $registrationDate,
                'created_at' => $registrationDate,
                'updated_at' => $registrationDate,
            ]);

            // Create 2 employees for each SME company
            for ($j = 1; $j <= 2; $j++) {
                User::create([
                    'fullname' => "SME $i Employee $j",
                    'email' => "sme$i.employee$j@halallink.com",
                    'password' => Hash::make('123'),
                    'companyID' => $smeCompany->companyID,
                    'role' => 'employee',
                    'status' => 'active',
                    'tel_number' => "6012346" . $i . str_pad($j, 2, '0', STR_PAD_LEFT),
                    'email_verified_at' => $registrationDate,
                    'created_at' => $registrationDate,
                    'updated_at' => $registrationDate,
                ]);
            }
        }

        // Admin | slaughterhouse - 1 account
        $slaughterhouseCompany = Company::create([
            'company_name' => 'Slaughterhouse Company',
            'company_type' => 'slaughterhouse',
        ]);

        // Admin user with no fullname
        User::create([
            'email' => 'slaughterhouse.admin@halallink.com',
            'password' => Hash::make('123'),
            'companyID' => $slaughterhouseCompany->companyID,
            'role' => 'admin',
            'status' => 'active',
            'tel_number' => '60123456730',
            'email_verified_at' => Carbon::now(),
        ]);

        // Create 2 employees for slaughterhouse company
        for ($i = 1; $i <= 2; $i++) {
            User::create([
                'fullname' => "Slaughterhouse Employee $i",
                'email' => "slaughterhouse.employee$i@halallink.com",
                'password' => Hash::make('123'),
                'companyID' => $slaughterhouseCompany->companyID,
                'role' => 'employee',
                'status' => 'active',
                'tel_number' => "6012347" . str_pad($i, 2, '0', STR_PAD_LEFT),
                'email_verified_at' => Carbon::now(),
            ]);
        }
    }
}