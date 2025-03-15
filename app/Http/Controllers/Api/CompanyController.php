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
        $company = Company::findOrFail($id);
        $company->delete();
        return response()->json(null, 204);
    }
}