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
     * @param  string  $role
     * @param  string|null  $companyType
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role, string $companyType = null)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = Auth::user();
        
        // Check if user has the required role
        if ($user->role !== $role) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }
        
        // If company type is specified, check if user belongs to that company type
        if ($companyType !== null) {
            $company = $user->company;
            if (!$company || $company->company_type !== $companyType) {
                return response()->json(['message' => 'Unauthorized access for this company type'], 403);
            }
        }

        return $next($request);
    }
}