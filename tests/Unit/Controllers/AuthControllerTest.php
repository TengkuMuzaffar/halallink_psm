<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_register_creates_company_and_admin_user()
    {
        Storage::fake('public');
        $logo = UploadedFile::fake()->image('company_logo.jpg');
        
        $response = $this->postJson('/api/auth/register', [
            'company_name' => 'Test Company',
            'company_type' => 'broiler',
            'company_logo' => $logo,
            'email' => 'admin@testcompany.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'tel_number' => '1234567890'
        ]);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('companies', ['company_name' => 'Test Company']);
        $this->assertDatabaseHas('users', ['email' => 'admin@testcompany.com']);
    }
    
    public function test_register_validates_input()
    {
        $response = $this->postJson('/api/auth/register', [
            'company_name' => '',
            'company_type' => 'invalid_type',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
            'tel_number' => ''
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['company_name', 'company_type', 'email', 'password', 'tel_number']);
    }
    
    public function test_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);
        
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user']);
    }
    
    public function test_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword'
        ]);
        
        $response->assertStatus(401);
    }
    
    public function test_logout_invalidates_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');
        
        $response->assertStatus(200);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}