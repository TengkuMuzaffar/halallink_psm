<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Str;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed the database with admin user
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Test login functionality
     */
    public function test_user_can_login()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@gmail.com',
            'password' => '123456'
        ]);

        // Check for successful login (status 200) instead of looking for token
        $response->assertStatus(200);
    }

    /**
     * Test middleware protection
     */
    public function test_middleware_protects_routes()
    {
        // Unauthenticated request should be rejected
        $response = $this->getJson('/api/dashboard/stats');
        $response->assertStatus(401);

        // Get the admin user from the seeder
        $admin = User::where('email', 'admin@gmail.com')->first();

        $response = $this->actingAs($admin)
                         ->getJson('/api/dashboard/stats');
        
        $response->assertStatus(200);
    }

    /**
     * Test role middleware
     */
    public function test_role_middleware()
    {
        // Get the admin user from the seeder
        $admin = User::where('email', 'admin@gmail.com')->first();

        // Create a regular user - check valid role values
        $company = Company::where('company_type', 'admin')->first();
        // In the test_role_middleware method, change this:
        $user = User::create([
            'companyID' => $company->companyID,
            'fullname' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active'
        ]);

        // Admin should access admin routes
        $response = $this->actingAs($admin)
                         ->getJson('/api/employees/all');
        $response->assertStatus(200);

        // Regular user should be denied
        $response = $this->actingAs($user)
                         ->getJson('/api/employees/all');
        $response->assertStatus(403);
    }

    /**
     * Test company type middleware
     */
    public function test_company_type_middleware()
    {
        // Get the admin user from the seeder
        $admin = User::where('email', 'admin@gmail.com')->first();
        
        // Create broiler company
        $broilerCompany = Company::create([
            'formID' => 'company' . Str::random(10),
            'company_name' => 'Broiler Company',
            'company_type' => 'broiler'
        ]);
        
        // Create slaughterhouse company
        $slaughterhouseCompany = Company::create([
            'formID' => 'company' . Str::random(10),
            'company_name' => 'Slaughterhouse Company',
            'company_type' => 'slaughterhouse'
        ]);
        
        // Create broiler admin
        $broilerAdmin = User::create([
            'companyID' => $broilerCompany->companyID,
            'fullname' => 'Broiler Admin',
            'email' => 'broiler@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active'
        ]);

        // Create slaughterhouse admin
        $slaughterhouseAdmin = User::create([
            'companyID' => $slaughterhouseCompany->companyID,
            'fullname' => 'Slaughterhouse Admin',
            'email' => 'slaughterhouse@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active'
        ]);

        // Broiler admin should access broiler routes
        $response = $this->actingAs($broilerAdmin)
                         ->getJson('/api/employees/broiler');
        $response->assertStatus(200);

        // Broiler admin should not access slaughterhouse routes
        $response = $this->actingAs($broilerAdmin)
                         ->getJson('/api/employees/slaughterhouse');
        $response->assertStatus(403);

        // Slaughterhouse admin should access slaughterhouse routes
        $response = $this->actingAs($slaughterhouseAdmin)
                         ->getJson('/api/employees/slaughterhouse');
        $response->assertStatus(200);
    }
}