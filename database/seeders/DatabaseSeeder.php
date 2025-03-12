<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create HalalLink company
        $company = Company::create([
            'formID' => 'companynx31ho672096',
            'company_name' => 'HalalLink',
            'company_type' => 'admin'
        ]);

        // Create admin user
        User::create([
            'companyID' => $company->companyID,
            'fullname' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('123456'),
            'role' => 'admin',
            'status' => 'active'
        ]);
    }
}
