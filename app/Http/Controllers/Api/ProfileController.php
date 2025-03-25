<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;
use App\Models\Location;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile()
    {
        $user = Auth::user();
        $data = [
            'userID' => $user->userID,
            'fullname' => $user->fullname,
            'email' => $user->email,
            'tel_number' => $user->tel_number,
            'status' => $user->status,
            'role' => $user->role,
            'image' => $user->image ? asset('storage/' . $user->image) : null,
        ];

        // If user is admin, include company data
        if ($user->role === 'admin' && $user->companyID) {
            $company = Company::find($user->companyID);
            if ($company) {
                $data['company'] = [
                    'companyID' => $company->companyID,
                    'company_name' => $company->company_name,
                    'company_image' => $company->company_image ? asset('storage/' . $company->company_image) : null,
                    'company_type' => $company->company_type,
                ];
                
                // Get company locations
                $locations = Location::where('companyID', $company->companyID)->get();
                $data['locations'] = $locations;
            }
        }

        return response()->json($data);
    }

    /**
     * Update the user's profile information
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        // Different validation rules based on role
        if ($user->role === 'admin') {
            $validator = Validator::make($request->all(), [
                'company_name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->userID . ',userID',
                'tel_number' => 'nullable|string|max:20',
                'company_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'fullname' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->userID . ',userID',
                'tel_number' => 'nullable|string|max:20',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update user data
        if ($user->role !== 'admin') {
            $user->fullname = $request->fullname;
        }
        
        $user->email = $request->email;
        $user->tel_number = $request->tel_number;
    
        // Handle profile image upload for non-admin users
        if (!$user->role === 'admin' && $request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }
            
            // Store new image
            $imagePath = $request->file('profile_image')->store('profile_images', 'public');
            $user->image = $imagePath;
        }

        $user->save();
    
        // If user is admin and company data is provided, update company
        $companyData = null;
        if ($user->role === 'admin' && $user->companyID) {
            $company = Company::find($user->companyID);
            if ($company && $request->has('company_name')) {
                $company->company_name = $request->company_name;
                
                // Handle company image upload
                if ($request->hasFile('company_image')) {
                    // Delete old image if exists
                    if ($company->company_image) {
                        Storage::disk('public')->delete($company->company_image);
                    }
                    
                    // Store new image
                    $imagePath = $request->file('company_image')->store('company_images', 'public');
                    $company->company_image = $imagePath;
                }
                
                $company->save();
                $companyData = [
                    'companyID' => $company->companyID,
                    'company_name' => $company->company_name,
                    'company_image' => $company->company_image ? asset('storage/' . $company->company_image) : null,
                    'company_type' => $company->company_type,
                ];
            }
        }

        // Prepare response data
        $responseData = [
            'message' => 'Profile updated successfully',
            'user' => [
                'userID' => $user->userID,
                'fullname' => $user->fullname,
                'email' => $user->email,
                'tel_number' => $user->tel_number,
                'status' => $user->status,
                'role' => $user->role,
                'image' => $user->image ? asset('storage/' . $user->image) : null,
            ]
        ];

        if ($companyData) {
            $responseData['company'] = $companyData;
        }

        return response()->json($responseData);
    }

    /**
     * Update user password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['errors' => ['current_password' => ['Current password is incorrect']]], 422);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully']);
    }

    /**
     * Manage company locations
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function manageLocations(Request $request)
    {
        $user = Auth::user();
        
        // Check if user is admin
        if ($user->role !== 'admin' || !$user->companyID) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'locations' => 'present|array', // Changed from 'required' to 'present' to allow empty arrays
            'locations.*.locationID' => 'nullable|integer|exists:locations,locationID',
            'locations.*.company_address' => 'required_with:locations.*.locationID|string|max:255',
            'locations.*.location_type' => 'required_with:locations.*.locationID|string|max:50',
            'delete_ids' => 'nullable|array',
            'delete_ids.*' => 'integer|exists:locations,locationID'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $companyID = $user->companyID;
        $savedLocations = [];
        
        // Only process locations if there are any
        if (!empty($request->locations)) {
            foreach ($request->locations as $locationData) {
                if (isset($locationData['locationID'])) {
                    // Update existing location
                    $location = Location::find($locationData['locationID']);
                    if ($location && $location->companyID == $companyID) {
                        $location->company_address = $locationData['company_address'];
                        $location->location_type = $locationData['location_type'];
                        $location->save();
                        $savedLocations[] = $location;
                    }
                } else {
                    // Create new location
                    $location = new Location([
                        'companyID' => $companyID,
                        'company_address' => $locationData['company_address'],
                        'location_type' => $locationData['location_type'],
                    ]);
                    $location->save();
                    $savedLocations[] = $location;
                }
            }
        }
        
        // If delete_ids are provided, delete those locations
        if ($request->has('delete_ids') && is_array($request->delete_ids)) {
            Location::whereIn('locationID', $request->delete_ids)
                ->where('companyID', $companyID)
                ->delete();
        }
        
        return response()->json([
            'message' => 'Locations updated successfully',
            'locations' => $savedLocations
        ]);
    }
}