<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company; // Ensure Company model is imported
use Illuminate\Support\Facades\Log; // Add this import

class CheckRoleAndCompanyType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role  Accepted values: 'admin', 'employee', 'both'
     * @param  string  ...$companyTypes  Allowed company types
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role = 'employee', ...$companyTypes)
    {
        if (!Auth::check()) {
            Log::debug('Company type check failed: ' . 'User not authenticated');

            // For web requests, redirect to login page
            if (!$request->expectsJson()) {
                return redirect('/login');
            }
            // For API requests, return JSON response
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();

        // Role check
        if ($role !== 'both' && $user->role !== $role) {
            // For web requests, redirect to login page
            if (!$request->expectsJson()) {
                return redirect('/login');
            }
            // For API requests, return JSON response
            return response()->json(['message' => 'Unauthorized role.'], 403);
        }
        
        // If role is 'both', it means any authenticated user role (admin or employee) is allowed,
        // so no specific role check against $user->role is needed beyond what 'both' implies.

        $company = $user->company; // Assuming user model has a 'company' relationship
        // Near the company type check
        Log::debug('Company type check', [
            'user_company_type' => $company->company_type,
            'allowed_types' => $companyTypes,
            'is_authenticated' => Auth::check(),
            'user_role' => $user->role
        ]);
        if (!$company) {
            Log::debug('Company type check failed: ' . 'Company not found');
            // For web requests, redirect to login page
            if (!$request->expectsJson()) {
                return redirect('/login');
            }
            // For API requests, return JSON response
            return response()->json(['message' => 'User not associated with a company.'], 403);
        }

        // Check company type - make sure $companyTypes is not empty
        if (empty($companyTypes)) {
            Log::debug('Company type check failed: ' . 'No company types specified');

            // For web requests, redirect to login page
            if (!$request->expectsJson()) {
                return redirect('/login');
            }
            // For API requests, return JSON response
            return response()->json(['message' => 'No company types specified for this route.'], 403);
        }

        // Check if the user's company type is in the allowed types
        if (!in_array($company->company_type, $companyTypes)) {
            Log::debug('Company type check failed: ' . 'Company type not allowed');

            // For web requests, redirect to login page
            if (!$request->expectsJson()) {
                return redirect('/login');
            }
            // For API requests, return JSON response
            return response()->json([
                'message' => 'Unauthorized access for this company type. Allowed: ' . implode(', ', $companyTypes) . '; Yours: ' . $company->company_type
            ], 403);
        }

        

        return $next($request);
    }
}