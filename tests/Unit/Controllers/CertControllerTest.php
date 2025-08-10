<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Cert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CertControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    protected $company;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'companyID' => $this->company->companyID
        ]);
        $this->actingAs($this->user);
    }
    
    public function test_get_certifications_returns_company_certs()
    {
        Cert::factory()->count(3)->create([
            'companyID' => $this->company->companyID
        ]);
        
        $response = $this->getJson('/api/certifications');
        
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'certifications');
        $response->assertJsonStructure([
            'certifications' => [
                '*' => ['certID', 'cert_type', 'cert_file', 'created_at', 'updated_at']
            ]
        ]);
    }
    
    public function test_get_certifications_returns_error_if_user_has_no_company()
    {
        $userWithoutCompany = User::factory()->create(['companyID' => null]);
        $this->actingAs($userWithoutCompany);
        
        $response = $this->getJson('/api/certifications');
        
        $response->assertStatus(400);
        $response->assertJson(['message' => 'User does not have a company']);
    }
    
    public function test_update_certifications_uploads_new_cert_files()
    {
        Storage::fake('public');
        
        $file1 = UploadedFile::fake()->create('halal_cert.pdf', 100);
        $file2 = UploadedFile::fake()->create('iso_cert.pdf', 100);
        
        $response = $this->postJson('/api/certifications/update', [
            'certifications' => [
                ['cert_type' => 'halal', 'cert_file' => $file1],
                ['cert_type' => 'iso', 'cert_file' => $file2]
            ]
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseCount('certs', 2);
        $this->assertDatabaseHas('certs', [
            'companyID' => $this->company->companyID,
            'cert_type' => 'halal'
        ]);
        $this->assertDatabaseHas('certs', [
            'companyID' => $this->company->companyID,
            'cert_type' => 'iso'
        ]);
    }
    
    public function test_delete_certification()
    {
        $cert = Cert::factory()->create([
            'companyID' => $this->company->companyID
        ]);
        
        $response = $this->deleteJson('/api/certifications/' . $cert->certID);
        
        $response->assertStatus(200);
        $this->assertDatabaseMissing('certs', ['certID' => $cert->certID]);
    }
}