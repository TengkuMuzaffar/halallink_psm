<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CompanyControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $adminUser;
    
    protected function setUp(): void
    {
        parent::setUp();
        $adminCompany = Company::factory()->create(['company_type' => 'admin']);
        $this->adminUser = User::factory()->create([
            'companyID' => $adminCompany->companyID,
            'role' => 'admin'
        ]);
        $this->actingAs($this->adminUser);
    }
    
    public function test_index_returns_paginated_companies()
    {
        Company::factory()->count(5)->create(['company_type' => 'broiler']);
        
        $response = $this->getJson('/api/companies?per_page=3');
        
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data',
            'pagination' => ['current_page', 'last_page', 'per_page', 'total']
        ]);
    }
    
    public function test_index_filters_by_company_type()
    {
        Company::factory()->create(['company_type' => 'broiler']);
        Company::factory()->create(['company_type' => 'slaughterhouse']);
        Company::factory()->create(['company_type' => 'sme']);
        
        $response = $this->getJson('/api/companies?company_type=broiler');
        
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJson([
            'data' => [
                ['company_type' => 'broiler']
            ]
        ]);
    }
    
    public function test_show_returns_company_details()
    {
        $company = Company::factory()->create();
        
        $response = $this->getJson('/api/companies/' . $company->companyID);
        
        $response->assertStatus(200);
        $response->assertJson([
            'companyID' => $company->companyID,
            'company_name' => $company->company_name
        ]);
    }
    
    public function test_update_company_details()
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $logo = UploadedFile::fake()->image('new_logo.jpg');
        
        $response = $this->postJson('/api/companies/' . $company->companyID, [
            'company_name' => 'Updated Company Name',
            'company_logo' => $logo
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('companies', [
            'companyID' => $company->companyID,
            'company_name' => 'Updated Company Name'
        ]);
    }
    
    public function test_approve_company()
    {
        $company = Company::factory()->create();
        $adminUser = User::factory()->create([
            'companyID' => $company->companyID,
            'role' => 'admin',
            'status' => 'inactive'
        ]);
        
        $response = $this->putJson('/api/companies/' . $company->companyID . '/approve');
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'userID' => $adminUser->userID,
            'status' => 'active'
        ]);
    }
    
    public function test_reject_company()
    {
        $company = Company::factory()->create();
        $adminUser = User::factory()->create([
            'companyID' => $company->companyID,
            'role' => 'admin',
            'status' => 'inactive'
        ]);
        
        $response = $this->putJson('/api/companies/' . $company->companyID . '/reject', [
            'reason' => 'Invalid documentation'
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'userID' => $adminUser->userID,
            'status' => 'rejected'
        ]);
    }
}