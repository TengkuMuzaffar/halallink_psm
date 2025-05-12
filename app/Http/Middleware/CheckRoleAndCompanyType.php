<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company; // Ensure Company model is imported

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
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();

        // Role check
        if ($role !== 'both' && $user->role !== $role) {
            return response()->json(['message' => 'Unauthorized role.'], 403);
        }
        
        // If role is 'both', it means any authenticated user role (admin or employee) is allowed,
        // so no specific role check against $user->role is needed beyond what 'both' implies.

        $company = $user->company; // Assuming user model has a 'company' relationship

        if (!$company) {
            return response()->json(['message' => 'User not associated with a company.'], 403);
        }

        // Check company type - make sure $companyTypes is not empty
        if (empty($companyTypes)) {
            return response()->json(['message' => 'No company types specified for this route.'], 403);
        }

        // // Debug information to help troubleshoot
        // \Illuminate\Support\Facades\Log::debug('Company type check', [
        //     'user_company_type' => $company->company_type,
        //     'allowed_types' => $companyTypes
        // ]);

        // Check if the user's company type is in the allowed types
        if (!in_array($company->company_type, $companyTypes)) {
            return response()->json([
                'message' => 'Unauthorized access for this company type. Allowed: ' . implode(', ', $companyTypes) . '; Yours: ' . $company->company_type
            ], 403);
        }

        return $next($request);
    }
}