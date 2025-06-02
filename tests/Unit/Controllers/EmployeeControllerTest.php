<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $adminUser;
    protected $company;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->adminUser = User::factory()->create([
            'companyID' => $this->company->companyID,
            'role' => 'admin'
        ]);
        $this->actingAs($this->adminUser);
    }
    
    public function test_index_returns_employees_for_company()
    {
        User::factory()->count(3)->create([
            'companyID' => $this->company->companyID,
            'role' => 'employee'
        ]);
        
        $response = $this->getJson('/api/employees');
        
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data',
            'pagination'
        ]);
    }
    
    public function test_store_creates_new_employee()
    {
        $response = $this->postJson('/api/employees', [
            'fullname' => 'Test Employee',
            'email' => 'employee@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tel_number' => '1234567890',
            'role' => 'driver'
        ]);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'employee@test.com',
            'companyID' => $this->company->companyID,
            'role' => 'driver'
        ]);
    }
    
    public function test_show_returns_employee_details()
    {
        $employee = User::factory()->create([
            'companyID' => $this->company->companyID,
            'role' => 'employee'
        ]);
        
        $response = $this->getJson('/api/employees/' . $employee->userID);
        
        $response->assertStatus(200);
        $response->assertJson([
            'userID' => $employee->userID,
            'email' => $employee->email
        ]);
    }
    
    public function test_update_employee_details()
    {
        $employee = User::factory()->create([
            'companyID' => $this->company->companyID,
            'role' => 'employee'
        ]);
        
        $response = $this->putJson('/api/employees/' . $employee->userID, [
            'fullname' => 'Updated Name',
            'tel_number' => '9876543210',
            'role' => 'driver'
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'userID' => $employee->userID,
            'fullname' => 'Updated Name',
            'tel_number' => '9876543210',
            'role' => 'driver'
        ]);
    }
    
    public function test_delete_employee()
    {
        $employee = User::factory()->create([
            'companyID' => $this->company->companyID,
            'role' => 'employee'
        ]);
        
        $response = $this->deleteJson('/api/employees/' . $employee->userID);
        
        $response->assertStatus(200);
        $this->assertSoftDeleted('users', ['userID' => $employee->userID]);
    }
    
    public function test_reset_employee_password()
    {
        $employee = User::factory()->create([
            'companyID' => $this->company->companyID,
            'role' => 'employee',
            'password' => Hash::make('oldpassword')
        ]);
        
        $response = $this->postJson('/api/employees/' . $employee->userID . '/reset-password', [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);
        
        $response->assertStatus(200);
        $updatedEmployee = User::find($employee->userID);
        $this->assertTrue(Hash::check('newpassword123', $updatedEmployee->password));
    }
}