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
            'company_name' => 'HalalLink Admin',
            'company_type' => 'admin',
            'company_image' => 'companies/admin-logo.png',
        ]);

        $this->createUser([
            'email' => 'admin@halallink.com',
            'password' => Hash::make('password123'),
            'companyID' => $adminCompany->companyID,
            'role' => 'admin',
            'status' => 'active',
            'tel_number' => '60123456789',
            'email_verified_at' => Carbon::now(),
        ]);

        // 2. Employee | admin - 2 accounts
        for ($i = 1; $i <= 2; $i++) {
            $this->createUser([
                'fullname' => "Admin Employee $i",
                'email' => "admin.employee$i@halallink.com",
                'password' => Hash::make('password123'),
                'companyID' => $adminCompany->companyID,
                'role' => 'employee',
                'status' => 'active',
                'tel_number' => "601234567" . (80 + $i),
                'email_verified_at' => Carbon::now(),
            ]);
        }

        // 3. Admin | broiler - 3 accounts
        for ($i = 1; $i <= 3; $i++) {
            $broilerCompany = Company::create([
                'company_name' => "Broiler Company $i",
                'company_type' => 'broiler',
                'company_image' => "companies/broiler-$i.png",
            ]);

            $this->createUser([
                    'email' => "broiler.admin$i@halallink.com",
                'password' => Hash::make('password123'),
                'companyID' => $broilerCompany->companyID,
                'role' => 'admin',
                'status' => 'active',
                'tel_number' => "601234567" . (70 + $i),
                'email_verified_at' => Carbon::now(),
            ]);

            // 4. Employee | broiler - 1 account each
            $this->createUser([
                'fullname' => "Broiler Employee $i",
                'email' => "broiler.employee$i@halallink.com",
                'password' => Hash::make('password123'),
                'companyID' => $broilerCompany->companyID,
                'role' => 'employee',
                'status' => 'active',
                'tel_number' => "601234567" . (60 + $i),
                'email_verified_at' => Carbon::now(),
            ]);
        }

        // 5. Admin | SME - 2 accounts
        for ($i = 1; $i <= 2; $i++) {
            $smeCompany = Company::create([
                'company_name' => "SME Company $i",
                'company_type' => 'sme',
                'company_image' => "companies/sme-$i.png",
            ]);

            $this->createUser([
                    'email' => "sme.admin$i@halallink.com",
                'password' => Hash::make('password123'),
                'companyID' => $smeCompany->companyID,
                'role' => 'admin',
                'status' => 'active',
                'tel_number' => "601234567" . (50 + $i),
                'email_verified_at' => Carbon::now(),
            ]);

            // 6. Employee | SME - 1 account each
            $this->createUser([
                'fullname' => "SME Employee $i",
                'email' => "sme.employee$i@halallink.com",
                'password' => Hash::make('password123'),
                'companyID' => $smeCompany->companyID,
                'role' => 'employee',
                'status' => 'active',
                'tel_number' => "601234567" . (40 + $i),
                'email_verified_at' => Carbon::now(),
            ]);
        }

        // 7. Admin | slaughterhouse - 1 account
        $slaughterhouseCompany = Company::create([
            'company_name' => 'Slaughterhouse Company',
            'company_type' => 'slaughterhouse',
            'company_image' => 'companies/slaughterhouse.png',
        ]);

        $this->createUser([
            'email' => 'slaughterhouse.admin@halallink.com',
            'password' => Hash::make('password123'),
            'companyID' => $slaughterhouseCompany->companyID,
            'role' => 'admin',
            'status' => 'active',
            'tel_number' => '60123456730',
            'email_verified_at' => Carbon::now(),
        ]);

        // 8. Employee | slaughterhouse - 1 account
        $this->createUser([
            'fullname' => 'Slaughterhouse Employee',
            'email' => 'slaughterhouse.employee@halallink.com',
            'password' => Hash::make('password123'),
            'companyID' => $slaughterhouseCompany->companyID,
            'role' => 'employee',
            'status' => 'active',
            'tel_number' => '60123456720',
            'email_verified_at' => Carbon::now(),
        ]);

        // 9. Admin | logistic - 1 account
        $logisticCompany = Company::create([
            'company_name' => 'Logistic Company',
            'company_type' => 'logistic',
            'company_image' => 'companies/logistic.png',
        ]);

        $this->createUser([
            'email' => 'logistic.admin@halallink.com',
            'password' => Hash::make('password123'),
            'companyID' => $logisticCompany->companyID,
            'role' => 'admin',
            'status' => 'active',
            'tel_number' => '60123456710',
            'email_verified_at' => Carbon::now(),
        ]);

        // 10. Employee | logistic - 1 account
        $this->createUser([
            'fullname' => 'Logistic Employee',
            'email' => 'logistic.employee@halallink.com',
            'password' => Hash::make('password123'),
            'companyID' => $logisticCompany->companyID,
            'role' => 'employee',
            'status' => 'active',
            'tel_number' => '60123456700',
            'email_verified_at' => Carbon::now(),
        ]);
    }

    /**
     * Helper method to create a user
     */
    private function createUser(array $data): User
    {
        return User::create($data);
    }
}