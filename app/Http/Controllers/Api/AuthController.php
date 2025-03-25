<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Company information
            'company_name' => 'required|string|max:255',
            'company_type' => 'required|in:broiler,slaughterhouse,SME,logistic',
            'company_image' => 'nullable|string',
            
            // Admin user details
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'tel_number' => 'required|string|max:20',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Create company first
        $company = Company::create([
            'formID' => 'company' . Str::random(10),
            'company_name' => $request->company_name,
            'company_type' => $request->company_type,
            'company_image' => $request->company_image,
        ]);
    
        // Create admin user for the company
        $user = User::create([
            'fullname' => $request->company_name . ' Admin', // Default name based on company
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tel_number' => $request->tel_number,
            'companyID' => $company->companyID,
            'role' => 'admin',
            'status' => 'active', // Admin users are active by default
        ]);
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        // Format company image URL
        $companyData = $company->toArray();
        if ($company->company_image) {
            $companyData['company_image'] = asset('storage/' . $company->company_image);
        }
        
        // Format user image URL
        $userData = $user->toArray();
        if ($user->image) {
            $userData['image'] = asset('storage/' . $user->image);
        }
    
        return response()->json([
            'company' => $companyData,
            'user' => $userData,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        
        // Check if user is active
        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your account is inactive. Please contact administrator.'
            ], 403);
        }

        // Load the company relationship
        $user->load('company');
        
        // Format user image URL
        $userData = $user->toArray();
        if ($user->image) {
            $userData['image'] = asset('storage/' . $user->image);
        }
        
        // Format company image URL if company exists
        if (isset($userData['company']) && $userData['company'] && isset($userData['company']['company_image']) && $userData['company']['company_image']) {
            $userData['company']['company_image'] = asset('storage/' . $userData['company']['company_image']);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $userData,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        $user->load('company');
        
        // Format user image URL
        $userData = $user->toArray();
        if ($user->image) {
            $userData['image'] = asset('storage/' . $user->image);
        }
        
        // Format company image URL if company exists
        if (isset($userData['company']) && $userData['company'] && isset($userData['company']['company_image']) && $userData['company']['company_image']) {
            $userData['company']['company_image'] = asset('storage/' . $userData['company']['company_image']);
        }
        
        return response()->json($userData);
    }
}
