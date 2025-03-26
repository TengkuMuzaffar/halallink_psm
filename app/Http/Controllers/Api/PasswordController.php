<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use App\Mail\PasswordChanged;
use App\Mail\ResetPasswordLink;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PasswordController extends Controller
{
    /**
     * Change password for authenticated user
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed', PasswordRule::defaults()],
        ]);

        $user = Auth::user();

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'The provided password does not match your current password.',
                'errors' => ['current_password' => ['The provided password does not match your current password.']]
            ], 422);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Send notification email
        Mail::to($user->email)->send(new PasswordChanged($user));

        return response()->json([
            'message' => 'Password updated successfully.'
        ]);
    }

    /**
     * Send a reset link to the given user
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        // Generate token
        $token = Str::random(64);
        
        // Store token in database
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        // Get user
        $user = User::where('email', $request->email)->first();
        
        // Send email with reset link
        Mail::to($request->email)->send(new ResetPasswordLink($user, $token));

        return response()->json([
            'message' => 'Password reset link sent to your email.'
        ]);
    }

    /**
     * Reset the user's password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed', PasswordRule::defaults()],
        ]);

        // Verify token
        $tokenData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$tokenData) {
            return response()->json([
                'message' => 'Invalid token or email.',
                'errors' => ['token' => ['Invalid token.']]
            ], 422);
        }

        // Check if token is expired (tokens valid for 60 minutes)
        $createdAt = Carbon::parse($tokenData->created_at);
        if (Carbon::now()->diffInMinutes($createdAt) > 60) {
            return response()->json([
                'message' => 'Token has expired.',
                'errors' => ['token' => ['Token has expired.']]
            ], 422);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Send notification email
        Mail::to($user->email)->send(new PasswordChanged($user));

        return response()->json([
            'message' => 'Password has been reset successfully.'
        ]);
    }

    /**
     * Validate reset token
     */
    public function validateToken(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        // Verify token
        $tokenData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$tokenData) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid token or email.'
            ], 422);
        }

        // Check if token is expired (tokens valid for 60 minutes)
        $createdAt = Carbon::parse($tokenData->created_at);
        if (Carbon::now()->diffInMinutes($createdAt) > 60) {
            return response()->json([
                'valid' => false,
                'message' => 'Token has expired.'
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Token is valid.'
        ]);
    }
}