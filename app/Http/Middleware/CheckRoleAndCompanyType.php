<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRoleAndCompanyType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $role
     * @param  string  $companyType
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role = 'employee', string $companyType)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = Auth::user();
        $company = $user->company;
        
        // Check if email is verified, except for profile routes
        $path = $request->path();
        if (!$user->hasVerifiedEmail() && !str_starts_with($path, 'api/profile')) {
            return response()->json([
                'message' => 'Email not verified',
                'verified' => false
            ], 403);
        }
        
        // Check role if specified
        // If role is 'both', allow both admin and employee roles
        if ($role !== 'both' && $role && $user->role !== $role) {
            return response()->json(['message' => 'Unauthorized access for this role'], 403);
        }
        
        // Check company type
        if (!$company || $company->company_type !== $companyType) {
            return response()->json(['message' => 'Unauthorized access for this company type'], 403);
        }

        return $next($request);
    }
}