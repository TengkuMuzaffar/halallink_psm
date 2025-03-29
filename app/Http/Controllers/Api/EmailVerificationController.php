<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmailVerificationController extends Controller
{
    /**
     * Send email verification link
     */
    public function sendVerificationEmail(Request $request)
    {
        $user = Auth::user();

        // Check if email is already verified
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified',
                'verified' => true
            ]);
        }

        // Generate token
        $token = Str::random(64);
        
        // Store token in database
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        // Send verification email
        Mail::to($user->email)->send(new VerifyEmail($user, $token));

        return response()->json([
            'message' => 'Verification link sent to your email address',
            'verified' => false
        ]);
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email'
        ]);

        $tokenData = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$tokenData) {
            return response()->json([
                'message' => 'Invalid verification link or link has expired'
            ], 400);
        }

        // Find user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Mark email as verified
        $user->markEmailAsVerified();

        // Delete the token
        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'message' => 'Email verified successfully',
            'verified' => true
        ]);
    }

    /**
     * Check if user's email is verified
     */
    public function checkVerificationStatus()
    {
        $user = Auth::user();
        
        return response()->json([
            'verified' => $user->hasVerifiedEmail()
        ]);
    }
}