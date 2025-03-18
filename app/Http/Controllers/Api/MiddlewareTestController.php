<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class MiddlewareTestController extends Controller
{
    /**
     * Test public route (no middleware)
     */
    public function publicTest()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Public route accessed successfully',
            'middleware_tests' => [
                'auth' => 'Not applicable',
                'session_activity' => 'Not applicable',
                'enhanced_token' => 'Not applicable',
                'role_company' => 'Not applicable'
            ]
        ]);
    }
    
    /**
     * Test basic authentication middleware
     */
    public function authTest(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Authentication middleware working correctly',
            'user' => $request->user(),
            'middleware_tests' => [
                'auth' => 'PASSED',
                'session_activity' => 'Not tested',
                'enhanced_token' => 'Not tested',
                'role_company' => 'Not tested'
            ]
        ]);
    }
    
    /**
     * Test session activity middleware
     */
    public function sessionTest(Request $request)
    {
        $lastActivity = $request->session()->get('last_activity');
        $sessionLifetime = config('session.lifetime', 120) * 60;
        $timeRemaining = ($lastActivity + $sessionLifetime) - time();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Session activity middleware working correctly',
            'session_data' => [
                'last_activity' => $lastActivity,
                'current_time' => time(),
                'session_lifetime_seconds' => $sessionLifetime,
                'session_expires_in_seconds' => $timeRemaining,
                'session_expires_at' => date('Y-m-d H:i:s', $lastActivity + $sessionLifetime)
            ],
            'middleware_tests' => [
                'auth' => 'PASSED',
                'session_activity' => 'PASSED',
                'enhanced_token' => 'Not tested',
                'role_company' => 'Not tested'
            ]
        ]);
    }
    
    /**
     * Test token security middleware
     */
    public function tokenTest(Request $request)
    {
        $user = $request->user();
        $token = $this->getTokenFromRequest($request);
        $tokenModel = null;
        $tokenExpiry = null;
        
        if ($token) {
            $tokenModel = PersonalAccessToken::findToken($token);
            if ($tokenModel && $tokenModel->expires_at) {
                $tokenExpiry = Carbon::parse($tokenModel->expires_at);
            }
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Token security middleware working correctly',
            'token_data' => [
                'token_exists' => !empty($token),
                'token_model_exists' => !empty($tokenModel),
                'token_expires_at' => $tokenExpiry ? $tokenExpiry->toIso8601String() : null,
                'token_expires_in_minutes' => $tokenExpiry ? $tokenExpiry->diffInMinutes(now()) : null,
                'token_is_expired' => $tokenExpiry ? $tokenExpiry->isPast() : null,
                'token_last_used' => $tokenModel ? $tokenModel->last_used_at : null
            ],
            'middleware_tests' => [
                'auth' => 'PASSED',
                'session_activity' => 'PASSED',
                'enhanced_token' => 'PASSED',
                'role_company' => 'Not tested'
            ]
        ]);
    }
    
    /**
     * Test role middleware (admin)
     */
    public function roleAdminTest(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Role middleware working correctly',
            'role_data' => [
                'user_role' => $user->role,
                'required_role' => 'admin',
                'company_type' => $user->company ? $user->company->company_type : null
            ],
            'middleware_tests' => [
                'auth' => 'PASSED',
                'session_activity' => 'PASSED',
                'enhanced_token' => 'PASSED',
                'role_company' => 'PASSED (role check only)'
            ]
        ]);
    }
    
    /**
     * Test role and company type middleware (admin,admin)
     */
    public function roleCompanyAdminTest(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Role and company type middleware working correctly',
            'role_data' => [
                'user_role' => $user->role,
                'required_role' => 'admin',
                'user_company_type' => $user->company ? $user->company->company_type : null,
                'required_company_type' => 'admin'
            ],
            'middleware_tests' => [
                'auth' => 'PASSED',
                'session_activity' => 'PASSED',
                'enhanced_token' => 'PASSED',
                'role_company' => 'PASSED (role and company type)'
            ]
        ]);
    }
    
    /**
     * Test role and company type middleware (admin,broiler)
     */
    public function roleCompanyBroilerTest(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Role and company type middleware working correctly',
            'role_data' => [
                'user_role' => $user->role,
                'required_role' => 'admin',
                'user_company_type' => $user->company ? $user->company->company_type : null,
                'required_company_type' => 'broiler'
            ],
            'middleware_tests' => [
                'auth' => 'PASSED',
                'session_activity' => 'PASSED',
                'enhanced_token' => 'PASSED',
                'role_company' => 'PASSED (role and company type)'
            ]
        ]);
    }
    
    /**
     * Test role and company type middleware (admin,slaughterhouse)
     */
    public function roleCompanySlaughterhouseTest(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Role and company type middleware working correctly',
            'role_data' => [
                'user_role' => $user->role,
                'required_role' => 'admin',
                'user_company_type' => $user->company ? $user->company->company_type : null,
                'required_company_type' => 'slaughterhouse'
            ],
            'middleware_tests' => [
                'auth' => 'PASSED',
                'session_activity' => 'PASSED',
                'enhanced_token' => 'PASSED',
                'role_company' => 'PASSED (role and company type)'
            ]
        ]);
    }
    
    /**
     * Artificially expire the session
     */
    public function expireSession(Request $request)
    {
        // Set last_activity to a time that would make the session appear expired
        $sessionLifetime = config('session.lifetime', 120) * 60;
        $expiredTime = time() - $sessionLifetime - 60; // 1 minute past expiration
        
        $request->session()->put('last_activity', $expiredTime);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Session marked as expired. Your next request should fail with SESSION_TIMEOUT.',
            'expired_time' => date('Y-m-d H:i:s', $expiredTime),
            'current_time' => date('Y-m-d H:i:s', time()),
            'session_lifetime_seconds' => $sessionLifetime
        ]);
    }
    
    /**
     * Artificially expire the token
     */
    public function expireToken(Request $request)
    {
        $user = $request->user();
        $token = $this->getTokenFromRequest($request);
        
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'No token found in request'
            ], 400);
        }
        
        $tokenModel = PersonalAccessToken::findToken($token);
        
        if (!$tokenModel) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token not found in database'
            ], 400);
        }
        
        // Set token expiration to 1 hour ago
        $tokenModel->expires_at = now()->subHour();
        $tokenModel->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Token marked as expired. Your next request should fail with TOKEN_EXPIRED.',
            'token_expires_at' => $tokenModel->expires_at->toIso8601String(),
            'current_time' => now()->toIso8601String()
        ]);
    }
    
    /**
     * Extract token from request
     *
     * @param Request $request
     * @return string|null
     */
    protected function getTokenFromRequest(Request $request)
    {
        $header = $request->header('Authorization', '');
        
        if (strpos($header, 'Bearer ') === 0) {
            return substr($header, 7);
        }
        
        return null;
    }
}