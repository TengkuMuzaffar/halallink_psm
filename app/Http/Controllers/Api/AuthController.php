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
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Company information
            'company_name' => 'required|string|max:255',
            'company_type' => 'required|in:broiler,slaughterhouse,sme,logistic',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Changed to accept image file
            
            // Admin user details
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'tel_number' => 'required|string|regex:/^\+60\s[0-9]{5}\s[0-9]{4}$/|max:20',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Handle company logo upload
        $companyImagePath = null;
        if ($request->hasFile('company_logo')) {
            $companyImagePath = $request->file('company_logo')->store('company_image', 'public');
        }
    
        // Create company first
        $company = Company::create([
            'formID' => 'company' . Str::random(10),
            'company_name' => $request->company_name,
            'company_type' => $request->company_type,
            'company_image' => $companyImagePath, // Store the file path
        ]);
    
        // Create admin user for the company with inactive status
        $user = User::create([
            'fullname' => $request->company_name . ' Admin', // Default name based on company
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tel_number' => $request->tel_number,
            'companyID' => $company->companyID,
            'role' => 'admin',
        ]);
    
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
            'message' => 'Registration successful. Your account is pending approval by an administrator.',
            'company' => $companyData,
            'user' => $userData,
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

        // First get the user to check status before attempting authentication
        $user = User::where('email', $request->email)->first();
        
        // If user doesn't exist, return invalid credentials
        if (!$user) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }
        
        // Check if user is active before attempting authentication
        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your account is inactive. Please wait for admin approval.'
            ], 403);
        }
        
        // If user is an employee, check if the company admin is active
        if ($user->role === 'employee' && $user->companyID) {
            // Find the admin of this company
            $companyAdmin = User::where('companyID', $user->companyID)
                ->where('role', 'admin')
                ->first();
                
            if (!$companyAdmin || $companyAdmin->status !== 'active') {
                return response()->json([
                    'message' => 'Company is not active. Please contact administrator.'
                ], 403);
            }
        }
        
        // Now attempt authentication since we know the user is active
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        // Reload the user to ensure we have fresh data
        $user = User::where('email', $request->email)->firstOrFail();
        
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
        // Delete all tokens for the current user
        $request->user()->tokens()->delete();

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

    public function registerEmployee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'formID' => 'required|string|exists:companies,formID',
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'tel_number' => 'required|string|regex:/^\+60\s[0-9]{5}\s[0-9]{4}$/|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Find the company by formID
        $company = Company::where('formID', $request->formID)->first();
        
        if (!$company) {
            return response()->json(['message' => 'Invalid company ID.'], 404);
        }
        
        // Handle employee image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('employee_image', 'public');
        }
    
        // Create employee user with inactive status
        $user = User::create([
            'fullname' => $request->fullname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tel_number' => $request->tel_number,
            'companyID' => $company->companyID,
            'role' => 'employee', // Default role is employee
            'status' => 'inactive', // Default status is inactive until approved
            'image' => $imagePath,
        ]);
    
        // Format user image URL
        $userData = $user->toArray();
        if ($user->image) {
            $userData['image'] = asset('storage/' . $user->image);
        }
    
        return response()->json([
            'message' => 'Registration successful. Your account is pending approval by your company administrator.',
            'user' => $userData,
        ], 201);
    }
}
