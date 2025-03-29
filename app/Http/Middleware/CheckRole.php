<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = Auth::user();
        
        // Check if email is verified, except for profile routes
        $path = $request->path();
        if (!$user->hasVerifiedEmail() && !str_starts_with($path, 'api/profile')) {
            return response()->json([
                'message' => 'Email not verified',
                'verified' => false
            ], 403);
        }
        
        // Check if user has the required role
        if ($user->role !== $role) {
            return response()->json(['message' => 'Unauthorized access for this role'], 403);
        }

        return $next($request);
    }
}