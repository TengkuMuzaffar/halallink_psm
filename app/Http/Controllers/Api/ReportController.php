<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReportValidity;
use App\Models\Report;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display a listing of all report validities.
     */
    public function index(Request $request)
    {
        try {
            // Get authenticated user
            $user = Auth::user();
            
            // Get pagination parameters
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            
            // Build query for report validities
            $query = ReportValidity::with(['user', 'reports.company']);
            
            // Apply filters if provided
            if ($request->has('approval')) {
                $query->where('approval', $request->approval);
            }
            
            if ($request->has('start_date')) {
                $query->whereDate('start_timestamp', '>=', $request->start_date);
            }
            
            if ($request->has('end_date')) {
                $query->whereDate('end_timestamp', '<=', $request->end_date);
            }
            
            // Get paginated results
            $reportValidities = $query->paginate($perPage, ['*'], 'page', $page);
            
            return response()->json([
                'success' => true,
                'data' => $reportValidities->items(),
                'pagination' => [
                    'current_page' => $reportValidities->currentPage(),
                    'last_page' => $reportValidities->lastPage(),
                    'per_page' => $reportValidities->perPage(),
                    'total' => $reportValidities->total(),
                    'from' => $reportValidities->firstItem(),
                    'to' => $reportValidities->lastItem(),
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve report validities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified report validity with related companies.
     */
    public function show(Request $request, $reportValidityID)
    {
        try {
            // Find the report validity
            $reportValidity = ReportValidity::with(['user', 'reports.company'])
                ->where('reportValidityID', $reportValidityID)
                ->first();
            
            if (!$reportValidity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Report validity not found'
                ], 404);
            }
            
            // Get companies related to this report validity
            $companies = Company::whereHas('reports', function($query) use ($reportValidityID) {
                $query->where('reportValidityID', $reportValidityID);
            })->get();
            
            return response()->json([
                'success' => true,
                'report_validity' => $reportValidity,
                'companies' => $companies
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve report validity details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created report validity and associated reports.
     */
    public function store(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'start_timestamp' => 'required|date',
                'end_timestamp' => 'required|date|after:start_timestamp',
                'approval' => 'boolean',
                'company_ids' => 'required|array',
                'company_ids.*' => 'exists:companies,companyID'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Get authenticated user
            $user = Auth::user();
            
            // Begin transaction
            DB::beginTransaction();
            
            // Create report validity
            $reportValidity = new ReportValidity();
            $reportValidity->userID = $user->userID;
            $reportValidity->start_timestamp = $request->start_timestamp;
            $reportValidity->end_timestamp = $request->end_timestamp;
            $reportValidity->approval = $request->input('approval', false);
            $reportValidity->save();
            
            // Create reports for each company
            foreach ($request->company_ids as $companyID) {
                $report = new Report();
                $report->companyID = $companyID;
                $report->reportValidityID = $reportValidity->reportValidityID;
                $report->save();
            }
            
            // Commit transaction
            DB::commit();
            
            // Load relationships for response
            $reportValidity->load(['user', 'reports.company']);
            
            return response()->json([
                'success' => true,
                'message' => 'Report validity created successfully',
                'data' => $reportValidity
            ], 201);
            
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create report validity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified report validity.
     * Note: start_timestamp and end_timestamp cannot be updated once created.
     */
    public function update(Request $request, $reportValidityID)
    {
        try {
            // Find the report validity
            $reportValidity = ReportValidity::findOrFail($reportValidityID);
            
            // Validate request
            $validator = Validator::make($request->all(), [
                'approval' => 'boolean',
                'company_ids' => 'array',
                'company_ids.*' => 'exists:companies,companyID'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Begin transaction
            DB::beginTransaction();
            
            // Update approval status if provided
            if ($request->has('approval')) {
                $reportValidity->approval = $request->approval;
                $reportValidity->save();
            }
            
            // Update associated companies if provided
            if ($request->has('company_ids')) {
                // Delete existing reports
                Report::where('reportValidityID', $reportValidityID)->delete();
                
                // Create new reports for each company
                foreach ($request->company_ids as $companyID) {
                    $report = new Report();
                    $report->companyID = $companyID;
                    $report->reportValidityID = $reportValidityID;
                    $report->save();
                }
            }
            
            // Commit transaction
            DB::commit();
            
            // Load relationships for response
            $reportValidity->load(['user', 'reports.company']);
            
            return response()->json([
                'success' => true,
                'message' => 'Report validity updated successfully',
                'data' => $reportValidity
            ]);
            
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update report validity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified report validity and its associated reports.
     */
    public function destroy($reportValidityID)
    {
        try {
            // Find the report validity
            $reportValidity = ReportValidity::findOrFail($reportValidityID);
            
            // Begin transaction
            DB::beginTransaction();
            
            // Delete associated reports first (should cascade, but being explicit)
            Report::where('reportValidityID', $reportValidityID)->delete();
            
            // Delete the report validity
            $reportValidity->delete();
            
            // Commit transaction
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Report validity deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete report validity',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}