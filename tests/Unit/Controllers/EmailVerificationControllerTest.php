<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;

class EmailVerificationControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email_verified_at' => null
        ]);
    }
    
    public function test_send_verification_email_creates_token_and_sends_email()
    {
        Mail::fake();
        
        $this->actingAs($this->user);
        $response = $this->postJson('/api/email/verification-notification');
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $this->user->email
        ]);
        Mail::assertSent(VerifyEmail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }
    
    public function test_send_verification_email_returns_already_verified_if_email_verified()
    {
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now()
        ]);
        
        $this->actingAs($verifiedUser);
        $response = $this->postJson('/api/email/verification-notification');
        
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Email already verified',
            'verified' => true
        ]);
    }
    
    public function test_verify_email_with_valid_token()
    {
        $token = 'valid_token';
        
        DB::table('password_reset_tokens')->insert([
            'email' => $this->user->email,
            'token' => $token,
            'created_at' => now()
        ]);
        
        $response = $this->getJson('/api/email/verify/' . $token);
        
        $response->assertStatus(200);
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $this->user->email
        ]);
        $this->assertNotNull(User::find($this->user->userID)->email_verified_at);
    }
    
    public function test_verify_email_with_invalid_token()
    {
        $response = $this->getJson('/api/email/verify/invalid_token');
        
        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Invalid verification token'
        ]);
    }
}