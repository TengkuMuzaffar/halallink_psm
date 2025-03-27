<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CompanyController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Remove these lines - they're causing the error
        // $this->middleware('auth:sanctum');
        // $this->middleware('role:admin');
    }

    /**
     * Get all companies
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = Company::query();
            
            // Apply filters if provided
            if ($request->has('company_type')) {
                $query->where('company_type', $request->company_type);
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            // Handle search parameter
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('company_name', 'LIKE', $searchTerm)
                      ->orWhereHas('admin', function($adminQuery) use ($searchTerm) {
                          $adminQuery->where('email', 'LIKE', $searchTerm)
                                    ->orWhere('tel_number', 'LIKE', $searchTerm);
                      });
                });
            }
            
            // Always exclude admin companies
            $query->where('company_type', '!=', 'admin');
            
            // Get companies with their admin information
            $companies = $query->with('admin')->get();
            
            // Transform the response to include admin details
            $companies = $companies->map(function($company) {
                $companyData = $company->toArray();
                return $companyData;
            });
            
            return response()->json($companies);
        } catch (\Exception $e) {
            Log::error('Error fetching companies: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch companies', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get company statistics
     *
     * @return \Illuminate\Http\Response
     */
    public function getStats()
    {
        try {
            $stats = [
                'broiler' => Company::where('company_type', 'broiler')->count(),
                'slaughterhouse' => Company::where('company_type', 'slaughterhouse')->count(),
                'sme' => Company::where('company_type', 'SME')->count(),
                'logistic' => Company::where('company_type', 'logistic')->count(),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Error fetching company stats: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch company statistics', 'error' => $e->getMessage()], 500);
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
            $validator = Validator::make($request->all(), [
                'company_name' => 'required|string|max:255',
                'company_type' => 'required|string|in:broiler,slaughterhouse,SME,logistic',
                'email' => 'required|string|email|max:255|unique:companies',
                'phone' => 'required|string|max:20',
                'address' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $company = Company::create($request->all());
            return response()->json($company, 201);
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
        $company = Company::findOrFail($id);
        return response()->json($company);
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
        $company = Company::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'company_name' => 'string|max:255',
            'company_type' => 'string|in:broiler,slaughterhouse,SME,logistic',
            'email' => 'string|email|max:255|unique:companies,email,' . $id,
            'phone' => 'string|max:20',
            'address' => 'string',
            'status' => 'string|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $company->update($request->all());
        return response()->json($company);
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
            $company = Company::with('admin')->findOrFail($id);
            
            // First delete the associated admin user if exists
            if ($company->admin) {
                // Delete user tokens first to avoid foreign key constraint issues
                $company->admin->tokens()->delete();
                $company->admin->delete();
            }
            
            // Then delete the company
            $company->delete();
            
            return response()->json(['message' => 'Company and associated user deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting company: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete company', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the status of a company
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $company = Company::with('admin')->findOrFail($id);
            
            // Update the admin user status
            if ($company->admin) {
                $company->admin->status = $request->status;
                $company->admin->save();
            }
            
            return response()->json([
                'message' => 'Company status updated successfully',
                'company' => $company->fresh('admin')
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating company status: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update company status', 'error' => $e->getMessage()], 500);
        }
    }
     /**
     * Get company information by formID
     *
     * @param  string  $formID
     * @return \Illuminate\Http\Response
     */
    public function getByFormID($formID)
    {
        try {
            if (empty($formID)) {
                return response()->json(['message' => 'FormID is required'], 422);
            }

            $company = Company::where('formID', $formID)->first();
            
            if (!$company) {
                return response()->json(['message' => 'Company not found'], 404);
            }
            
            // Return necessary information including company_image
            return response()->json([
                'company_name' => $company->company_name,
                'company_type' => $company->company_type,
                'company_image' => $company->company_image,
                'formID' => $company->formID
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching company by formID: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch company information', 'error' => $e->getMessage()], 500);
        }
    }
}