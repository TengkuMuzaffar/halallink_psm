<?php

namespace Tests\Unit;

use App\Http\Middleware\CheckRoleAndCompanyType;
use App\Http\Middleware\EnhancedTokenSecurity;
use App\Http\Middleware\SessionActivity;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;
use Mockery;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->adminCompany = Company::create([
            'formID' => 'company' . Str::random(10),
            'company_name' => 'Admin Company',
            'company_type' => 'admin'
        ]);
        
        $this->broilerCompany = Company::create([
            'formID' => 'company' . Str::random(10),
            'company_name' => 'Broiler Company',
            'company_type' => 'broiler'
        ]);
        
        $this->adminUser = User::create([
            'companyID' => $this->adminCompany->companyID,
            'fullname' => 'Test Admin',
            'email' => 'testadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        $this->broilerAdmin = User::create([
            'companyID' => $this->broilerCompany->companyID,
            'fullname' => 'Broiler Admin',
            'email' => 'broileradmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        $this->employeeUser = User::create([
            'companyID' => $this->adminCompany->companyID,
            'fullname' => 'Employee User',
            'email' => 'employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'status' => 'active'
        ]);
    }

    // For the CheckRoleAndCompanyType middleware test
    public function test_check_role_middleware_only()
    {
        $middleware = new CheckRoleAndCompanyType();
        
        // Mock request and closure
        $request = new Request();
        $request->setUserResolver(function () {
            return $this->adminUser;
        });
        
        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;
            return response('OK');
        };
        
        // Test 1: Admin user with admin role - should pass
        $middleware->handle($request, $next, 'admin');
        $this->assertTrue($called, 'Middleware did not call next for admin user');
        $called = false;
        
        // Test 2: Admin user with employee role requirement - should fail
        $response = $middleware->handle($request, $next, 'employee');
        $this->assertFalse($called, 'Middleware incorrectly called next for wrong role');
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Unauthorized access', $response->getContent());
        
        // Test 3: Employee user with employee role requirement - should pass
        $request->setUserResolver(function () {
            return $this->employeeUser;
        });
        
        $middleware->handle($request, $next, 'employee');
        $this->assertTrue($called, 'Middleware did not call next for employee user with correct role');
    }
    
    // For the SessionActivity middleware test
    public function test_session_activity_middleware_unit()
    {
        $middleware = new SessionActivity();
        
        // Mock request and closure
        $request = new Request();
        $request->setUserResolver(function () {
            return $this->adminUser;
        });
        
        // Mock session
        $request->setLaravelSession(app('session.store'));
        
        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;
            return response('OK');
        };
        
        // Test 1: No last_activity - should set it and pass
        app('session.store')->forget('last_activity');
        $middleware->handle($request, $next);
        $this->assertTrue($called, 'Middleware did not call next for new session');
        $this->assertNotNull(app('session.store')->get('last_activity'), 'Middleware did not set last_activity');
        $called = false;
        
        // Test 2: Recent last_activity - should update and pass
        $lastActivity = time() - 60; // 1 minute ago
        app('session.store')->put('last_activity', $lastActivity);
        $middleware->handle($request, $next);
        $this->assertTrue($called, 'Middleware did not call next for recent activity');
        $this->assertGreaterThan($lastActivity, app('session.store')->get('last_activity'), 'Middleware did not update last_activity');
        $called = false;
        
        // Test 3: Expired last_activity - should fail
        $sessionLifetime = config('session.lifetime', 120) * 60;
        $expiredTime = time() - $sessionLifetime - 60; // 1 minute past expiration
        app('session.store')->put('last_activity', $expiredTime);
        
        $response = $middleware->handle($request, $next);
        $this->assertFalse($called, 'Middleware incorrectly called next for expired session');
        $this->assertEquals(401, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('SESSION_TIMEOUT', $responseData['code']);
    }
    
    // For the EnhancedTokenSecurity middleware test
    public function test_enhanced_token_security_middleware_unit()
    {
        $middleware = new EnhancedTokenSecurity();
        
        // Create a token for testing
        $token = $this->adminUser->createToken('test-token');
        $plainTextToken = $token->plainTextToken;
        $tokenParts = explode('|', $plainTextToken);
        $tokenModel = PersonalAccessToken::findToken($tokenParts[1]);
        
        // Mock request and closure
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer ' . $plainTextToken);
        $request->setUserResolver(function () {
            return $this->adminUser;
        });
        
        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;
            return response('OK');
        };
        
        // Test 1: Valid token - should update last_used_at and pass
        $middleware->handle($request, $next);
        $this->assertTrue($called, 'Middleware did not call next for valid token');
        
        $tokenModel->refresh();
        $this->assertNotNull($tokenModel->last_used_at, 'Middleware did not update last_used_at');
        $called = false;
        
        // Test 2: Expired token - should fail
        $tokenModel->expires_at = now()->subHour();
        $tokenModel->save();
        
        $response = $middleware->handle($request, $next);
        $this->assertFalse($called, 'Middleware incorrectly called next for expired token');
        $this->assertEquals(401, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('TOKEN_EXPIRED', $responseData['code']);
        
        // Test 3: Token about to expire - should refresh and pass
        $tokenModel->expires_at = now()->addMinutes(4); // Less than 5 minutes to expiry
        $tokenModel->save();
        
        $response = $middleware->handle($request, $next);
        $this->assertTrue($called, 'Middleware did not call next for token about to expire');
        
        $tokenModel->refresh();
        $this->assertGreaterThan(now()->addMinutes(50), $tokenModel->expires_at, 'Middleware did not refresh token expiry');
    }
    
    /**
     * Test middleware with missing or invalid token
     */
    public function test_enhanced_token_security_with_invalid_token()
    {
        $middleware = new EnhancedTokenSecurity();
        
        // Mock request and closure
        $request = new Request();
        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;
            return response('OK');
        };
        
        // Test 1: No token - should pass to next middleware (auth will handle it)
        $response = $middleware->handle($request, $next);
        $this->assertTrue($called, 'Middleware did not call next for missing token');
        $called = false;
        
        // Test 2: Invalid token format - should pass to next middleware (auth will handle it)
        $request->headers->set('Authorization', 'Bearer invalid-token');
        $response = $middleware->handle($request, $next);
        $this->assertTrue($called, 'Middleware did not call next for invalid token format');
        $called = false;
        
        // Test 3: Non-existent token - should pass to next middleware (auth will handle it)
        $request->headers->set('Authorization', 'Bearer 1|nonexistenttoken123456789012345678901234567890');
        $response = $middleware->handle($request, $next);
        $this->assertTrue($called, 'Middleware did not call next for non-existent token');
    }
    
    /**
     * Test middleware with unauthenticated user
     */
    public function test_middleware_with_unauthenticated_user()
    {
        // Test CheckRoleAndCompanyType middleware
        $roleMiddleware = new CheckRoleAndCompanyType();
        $request = new Request();
        $request->setUserResolver(function () {
            return null;
        });
        
        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;
            return response('OK');
        };
        
        $response = $roleMiddleware->handle($request, $next, 'admin');
        $this->assertFalse($called, 'Role middleware incorrectly called next for unauthenticated user');
        $this->assertEquals(401, $response->getStatusCode());
        
        // Test SessionActivity middleware
        $sessionMiddleware = new SessionActivity();
        $response = $sessionMiddleware->handle($request, $next);
        $this->assertTrue($called, 'Session middleware did not call next for unauthenticated user');
    }
    
    /**
     * Test CheckRoleAndCompanyType middleware with role and company type
     */
    public function test_check_role_and_company_type_middleware()
    {
        $middleware = new CheckRoleAndCompanyType();
        
        // Mock request and closure
        $request = new Request();
        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;
            return response('OK');
        };
        
        // Test 1: Admin user with admin company type - should pass
        $request->setUserResolver(function () {
            return $this->adminUser;
        });
        
        $middleware->handle($request, $next, 'admin', 'admin');
        $this->assertTrue($called, 'Middleware did not call next for admin user with admin company');
        $called = false;
        
        // Test 2: Admin user with broiler company type requirement - should fail
        $response = $middleware->handle($request, $next, 'admin', 'broiler');
        $this->assertFalse($called, 'Middleware incorrectly called next for wrong company type');
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Unauthorized access for this company type', $response->getContent());
        
        // Test 3: Broiler admin with broiler company type - should pass
        $request->setUserResolver(function () {
            return $this->broilerAdmin;
        });
        
        $middleware->handle($request, $next, 'admin', 'broiler');
        $this->assertTrue($called, 'Middleware did not call next for broiler admin with correct company type');
        $called = false;
        
        // Test 4: Broiler admin with admin company type - should fail
        $response = $middleware->handle($request, $next, 'admin', 'admin');
        $this->assertFalse($called, 'Middleware incorrectly called next for wrong company type');
        $this->assertEquals(403, $response->getStatusCode());
    }
    
    /**
     * Test middleware with user that has no company
     */
    public function test_middleware_with_user_without_company()
    {
        // Create user without company
        $userWithoutCompany = User::create([
            'companyID' => null,
            'fullname' => 'No Company User',
            'email' => 'nocompany@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active'
        ]);
        
        $middleware = new CheckRoleAndCompanyType();
        
        // Mock request and closure
        $request = new Request();
        $request->setUserResolver(function () use ($userWithoutCompany) {
            return $userWithoutCompany;
        });
        
        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;
            return response('OK');
        };
        
        // Test 1: Role check should pass
        $middleware->handle($request, $next, 'admin');
        $this->assertTrue($called, 'Middleware did not call next for user without company (role check only)');
        $called = false;
        
        // Test 2: Company type check should fail
        $response = $middleware->handle($request, $next, 'admin', 'admin');
        $this->assertFalse($called, 'Middleware incorrectly called next for user without company (company check)');
        $this->assertEquals(403, $response->getStatusCode());
    }
    
    /**
     * Test middleware with inactive user
     */
    public function test_middleware_with_inactive_user()
    {
        // Create inactive user
        $inactiveUser = User::create([
            'companyID' => $this->adminCompany->companyID,
            'fullname' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'inactive'
        ]);
        
        // Test with CheckRoleAndCompanyType middleware
        $roleMiddleware = new CheckRoleAndCompanyType();
        $request = new Request();
        $request->setUserResolver(function () use ($inactiveUser) {
            return $inactiveUser;
        });
        
        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;
            return response('OK');
        };
        
        // Role check should still pass since we're only checking role, not status
        $roleMiddleware->handle($request, $next, 'admin');
        $this->assertTrue($called, 'Middleware did not call next for inactive user with correct role');
    }
    
    /**
     * Test SessionActivity middleware with different session configurations
     */
    public function test_session_activity_with_different_lifetimes()
    {
        $middleware = new SessionActivity();
        
        // Mock request and closure
        $request = new Request();
        $request->setUserResolver(function () {
            return $this->adminUser;
        });
        
        // Mock session
        $request->setLaravelSession(app('session.store'));
        
        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;
            return response('OK');
        };
        
        // Test with custom session lifetime
        $originalLifetime = config('session.lifetime');
        config(['session.lifetime' => 5]); // 5 minutes
        
        // Set last_activity to 4 minutes ago (within lifetime)
        $lastActivity = time() - 240; // 4 minutes ago
        app('session.store')->put('last_activity', $lastActivity);
        
        $middleware->handle($request, $next);
        $this->assertTrue($called, 'Middleware did not call next for activity within custom lifetime');
        $called = false;
        
        // Set last_activity to 6 minutes ago (expired)
        $lastActivity = time() - 360; // 6 minutes ago
        app('session.store')->put('last_activity', $lastActivity);
        
        $response = $middleware->handle($request, $next);
        $this->assertFalse($called, 'Middleware incorrectly called next for expired session with custom lifetime');
        $this->assertEquals(401, $response->getStatusCode());
        
        // Restore original config
        config(['session.lifetime' => $originalLifetime]);
    }
    
    /**
     * Test EnhancedTokenSecurity middleware with different token configurations
     */
    public function test_enhanced_token_security_with_different_refresh_thresholds()
    {
        // This test would be more comprehensive if the refresh threshold was configurable
        // For now, we'll test the fixed 5-minute threshold
        
        $middleware = new EnhancedTokenSecurity();
        
        // Create a token for testing
        $token = $this->adminUser->createToken('test-token');
        $plainTextToken = $token->plainTextToken;
        $tokenParts = explode('|', $plainTextToken);
        $tokenModel = PersonalAccessToken::findToken($tokenParts[1]);
        
        // Mock request and closure
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer ' . $plainTextToken);
        $request->setUserResolver(function () {
            return $this->adminUser;
        });
        
        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;
            return response('OK');
        };
        
        // Test with token exactly 5 minutes from expiry
        $tokenModel->expires_at = now()->addMinutes(5);
        $tokenModel->save();
        
        $middleware->handle($request, $next);
        $this->assertTrue($called, 'Middleware did not call next for token at refresh threshold');
        
        $tokenModel->refresh();
        // Token should be refreshed if exactly at threshold (5 minutes)
        $this->assertGreaterThan(now()->addMinutes(50), $tokenModel->expires_at, 'Middleware did not refresh token at threshold');
    }
    
    /**
     * Test middleware chain with multiple middleware in sequence
     */
    public function test_middleware_chain_sequence()
    {
        // Create middleware instances
        $sessionMiddleware = new SessionActivity();
        $tokenMiddleware = new EnhancedTokenSecurity();
        $roleMiddleware = new CheckRoleAndCompanyType();
        
        // Create a token for testing
        $token = $this->adminUser->createToken('test-token');
        $plainTextToken = $token->plainTextToken;
        $tokenParts = explode('|', $plainTextToken);
        $tokenModel = PersonalAccessToken::findToken($tokenParts[1]);
        $tokenModel->expires_at = now()->addHour();
        $tokenModel->save();
        
        // Mock request and closure
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer ' . $plainTextToken);
        $request->setUserResolver(function () {
            return $this->adminUser;
        });
        $request->setLaravelSession(app('session.store'));
        app('session.store')->put('last_activity', time());
        
        $called = false;
        $next = function ($req) use (&$called) {
            $called = true;
            return response('OK');
        };
        
        // Test successful chain
        $response = $sessionMiddleware->handle($request, function ($req) use ($tokenMiddleware, $roleMiddleware, $next) {
            return $tokenMiddleware->handle($req, function ($req) use ($roleMiddleware, $next) {
                return $roleMiddleware->handle($req, $next, 'admin', 'admin');
            });
        });
        
        $this->assertTrue($called, 'Middleware chain did not call next for valid request');
        
        // Test chain with expired session
        $called = false;
        app('session.store')->put('last_activity', time() - config('session.lifetime', 120) * 60 - 60);
        
        $response = $sessionMiddleware->handle($request, function ($req) use ($tokenMiddleware, $roleMiddleware, $next) {
            return $tokenMiddleware->handle($req, function ($req) use ($roleMiddleware, $next) {
                return $roleMiddleware->handle($req, $next, 'admin', 'admin');
            });
        });
        
        $this->assertFalse($called, 'Middleware chain incorrectly called next for expired session');
        $this->assertEquals(401, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('SESSION_TIMEOUT', $responseData['code']);
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}