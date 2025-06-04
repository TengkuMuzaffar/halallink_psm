<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // No middleware here - they're applied in the routes
    }

    /**
     * Get all companies
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            // Start with a base query that excludes admin companies
            $query = Company::where('company_type', '!=', 'admin');
            
            // Apply company type filter if provided
            if ($request->has('company_type') && !empty($request->company_type)) {
                $query->where('company_type', $request->company_type);
            }
            
            // Apply status filter to admin users if provided
            if ($request->has('status') && !empty($request->status)) {
                $query->whereHas('admin', function($adminQuery) use ($request) {
                    $adminQuery->where('status', $request->status);
                });
            }
            
            // Handle search parameter more efficiently
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('company_name', 'LIKE', $searchTerm)
                      ->orWhere('company_type', 'LIKE', $searchTerm)
                      ->orWhereHas('admin', function($adminQuery) use ($searchTerm) {
                          $adminQuery->where('email', 'LIKE', $searchTerm)
                                    ->orWhere('tel_number', 'LIKE', $searchTerm);
                      });
                });
            }
            
            // Get companies with their admin information with pagination
            $perPage = $request->input('per_page', 3); // Default to 3 items per page
            $page = $request->input('page', 1);
            
            $paginatedCompanies = $query->with(['admin' => function($query) {
                $query->select('userID', 'companyID', 'email', 'tel_number', 'status', 'created_at');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
            
            // Format the response to match what the frontend expects
            return response()->json([
                'success' => true,
                'data' => $paginatedCompanies->items(),
                'pagination' => [
                    'current_page' => $paginatedCompanies->currentPage(),
                    'last_page' => $paginatedCompanies->lastPage(),
                    'per_page' => $paginatedCompanies->perPage(),
                    'total' => $paginatedCompanies->total(),
                    'from' => $paginatedCompanies->firstItem(),
                    'to' => $paginatedCompanies->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching companies: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch companies',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get company statistics
     *
     * @return \Illuminate\Http\Response
     */
    public function getStats(Request $request)
    {
        try {
            // Start with a base query that excludes admin companies
            $baseQuery = Company::where('company_type', '!=', 'admin');
            
            // Apply search filter if provided
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = '%' . $request->search . '%';
                $baseQuery->where(function($q) use ($searchTerm) {
                    $q->where('company_name', 'LIKE', $searchTerm)
                      ->orWhere('company_type', 'LIKE', $searchTerm)
                      ->orWhereHas('admin', function($adminQuery) use ($searchTerm) {
                          $adminQuery->where('email', 'LIKE', $searchTerm)
                                    ->orWhere('tel_number', 'LIKE', $searchTerm);
                      });
                });
            }
            
            // Apply status filter if provided
            if ($request->has('status') && !empty($request->status)) {
                $baseQuery->whereHas('admin', function($adminQuery) use ($request) {
                    $adminQuery->where('status', $request->status);
                });
            }
            
            // Clone the base query for each company type
            $broilerQuery = clone $baseQuery;
            $slaughterhouseQuery = clone $baseQuery;
            $smeQuery = clone $baseQuery;
            $logisticQuery = clone $baseQuery;
            
            $stats = [
                'broiler' => $broilerQuery->where('company_type', 'broiler')->count(),
                'slaughterhouse' => $slaughterhouseQuery->where('company_type', 'slaughterhouse')->count(),
                'sme' => $smeQuery->where('company_type', 'sme')->count(),
                'logistic' => $logisticQuery->where('company_type', 'logistic')->count(),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Error fetching company stats: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch company statistics', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get company by formID (public route)
     *
     * @param  string  $formID
     * @return \Illuminate\Http\Response
     */
    /**
     * Get company by formID
     *
     * @param  string  $formID
     * @return \Illuminate\Http\Response
     */
    public function getByFormID($formID)
    {
        try {
            $company = Company::where('formID', $formID)->first();
            
            if (!$company) {
                return response()->json(['message' => 'Company not found'], 404);
            }
            
            // Format company image URL
            $data = [
                'companyID' => $company->companyID,
                'formID' => $company->formID,
                'company_name' => $company->company_name,
                'company_type' => $company->company_type,
                'company_image' => $company->company_image ? asset('storage/' . $company->company_image) : null,
            ];
            
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error fetching company by formID: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch company', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created company
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Validate company data
            $validator = Validator::make($request->all(), [
                'company_name' => 'required|string|max:255',
                'company_type' => 'required|string|in:broiler,slaughterhouse,SME,logistic',
                'company_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                
                // Admin user details
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'tel_number' => 'required|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Handle company image if provided
            $imagePath = null;
            if ($request->hasFile('company_image')) {
                $imagePath = $request->file('company_image')->store('company_images', 'public');
            }

            // Create company
            $company = new Company();
            $company->company_name = $request->company_name;
            $company->company_type = $request->company_type;
            $company->company_image = $imagePath;
            $company->status = 'active';
            $company->save();

            // Create admin user for the company
            $admin = new User();
            $admin->fullname = $request->fullname ?? $request->company_name . ' Admin';
            $admin->email = $request->email;
            $admin->password = Hash::make($request->password);
            $admin->tel_number = $request->tel_number;
            $admin->role = 'admin';
            $admin->status = 'active';
            $admin->companyID = $company->companyID;
            $admin->save();

            return response()->json([
                'message' => 'Company created successfully',
                'company' => $company,
                'admin' => $admin
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating company: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create company', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified company
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $company = Company::with('admin')->find($id);
            
            if (!$company) {
                return response()->json(['message' => 'Company not found'], 404);
            }
            
            return response()->json($company);
        } catch (\Exception $e) {
            Log::error('Error fetching company: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch company', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified company
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $company = Company::find($id);
            
            if (!$company) {
                return response()->json(['message' => 'Company not found'], 404);
            }
            
            // Validate request
            $validator = Validator::make($request->all(), [
                'company_name' => 'string|max:255',
                'company_type' => 'string|in:broiler,slaughterhouse,SME,logistic',
                'company_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            // Handle image update if provided
            if ($request->hasFile('company_image')) {
                // Delete old image if exists
                if ($company->company_image) {
                    Storage::disk('public')->delete($company->company_image);
                }
                
                // Store new image
                $imagePath = $request->file('company_image')->store('company_images', 'public');
                $company->company_image = $imagePath;
            }
            
            // Update company fields
            if ($request->has('company_name')) $company->company_name = $request->company_name;
            if ($request->has('company_type')) $company->company_type = $request->company_type;
            
            $company->save();
            
            return response()->json([
                'message' => 'Company updated successfully',
                'company' => $company
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating company: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update company', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update company status
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $company = Company::with('admin')->find($id);
            
            if (!$company) {
                return response()->json(['message' => 'Company not found'], 404);
            }
            
            // Validate request
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:active,inactive',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            // Update admin user status
            if ($company->admin) {
                $company->admin->status = $request->status;
                $company->admin->save();
            }
            
            return response()->json([
                'message' => 'Company status updated successfully',
                'company' => Company::with('admin')->find($id)
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating company status: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update company status', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified company
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $company = Company::find($id);
            
            if (!$company) {
                return response()->json(['message' => 'Company not found'], 404);
            }
            
            // Delete company image if exists
            if ($company->company_image) {
                Storage::disk('public')->delete($company->company_image);
            }
            
            // Delete associated users
            $users = User::where('companyID', $id)->get();
            foreach ($users as $user) {
                // Delete user image if exists
                if ($user->image) {
                    Storage::disk('public')->delete($user->image);
                }
                
                // Delete user tokens
                $user->tokens()->delete();
                
                // Delete user
                $user->delete();
            }
            
            // Delete company
            $company->delete();
            
            return response()->json(['message' => 'Company and associated users deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting company: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete company', 'error' => $e->getMessage()], 500);
        }
    }
}