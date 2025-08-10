<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /**
     * Get employees for the current admin's company with pagination, search, and filters
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return response()->json(['message' => 'Unauthorized. Only company admins can access employee data.'], 403);
            }
            
            // Start with a base query for employees from the same company, excluding the admin
            $query = User::where('companyID', $user->companyID)
                ->where('userID', '!=', $user->userID)
                ->where('role', '!=', 'admin');
            
            // Apply status filter if provided
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }
            
            // Apply search if provided
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', $searchTerm)
                      ->orWhere('email', 'LIKE', $searchTerm)
                      ->orWhere('phone', 'LIKE', $searchTerm);
                });
            }
            
            // Apply sorting if provided
            $sortField = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            
            // Validate sort field to prevent SQL injection
            $allowedSortFields = ['name', 'email', 'phone', 'status', 'created_at'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'created_at';
            }
            
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
            
            // Get pagination parameters
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            
            // Get paginated results directly from the database
            $employees = $query->paginate($perPage, ['*'], 'page', $page);
            
            // Get employee stats
            $stats = [
                'total' => User::where('companyID', $user->companyID)
                    ->where('userID', '!=', $user->userID)
                    ->where('role', '!=', 'admin')
                    ->count(),
                'active' => User::where('companyID', $user->companyID)
                    ->where('userID', '!=', $user->userID)
                    ->where('role', '!=', 'admin')
                    ->where('status', 'active')
                    ->count(),
                'inactive' => User::where('companyID', $user->companyID)
                    ->where('userID', '!=', $user->userID)
                    ->where('role', '!=', 'admin')
                    ->where('status', 'inactive')
                    ->count()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $employees->items(),
                'pagination' => [
                    'current_page' => $employees->currentPage(),
                    'last_page' => $employees->lastPage(),
                    'per_page' => $employees->perPage(),
                    'total' => $employees->total(),
                    'from' => $employees->firstItem(),
                    'to' => $employees->lastItem()
                ],
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching employees: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch employees', 
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Store a newly created employee
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return response()->json(['message' => 'Unauthorized. Only company admins can create employees.'], 403);
            }
            
            // Return response indicating direct employee creation is not available
            return response()->json([
                'message' => 'Direct employee creation is not available. Please use the employee registration link instead.'
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Error in employee store method: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Display the specified employee
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return response()->json(['message' => 'Unauthorized. Only company admins can view employee details.'], 403);
            }
            
            // Get employee from the same company
            $employee = User::where('userID', $id)
                ->where('companyID', $user->companyID)
                ->where('role', '!=', 'admin')
                ->first();
            
            if (!$employee) {
                return response()->json(['message' => 'Employee not found or not authorized to access'], 404);
            }
            
            return response()->json($employee);
        } catch (\Exception $e) {
            Log::error('Error fetching employee: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch employee', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Update the specified employee
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return response()->json(['message' => 'Unauthorized. Only company admins can update employees.'], 403);
            }
            
            // Get employee from the same company
            $employee = User::where('userID', $id)
                ->where('companyID', $user->companyID)
                ->where('role', '!=', 'admin')
                ->first();
            
            if (!$employee) {
                return response()->json(['message' => 'Employee not found or not authorized to update'], 404);
            }
            
            // Validate request
            $validator = Validator::make($request->all(), [
                'fullname' => 'string|max:255',
                'email' => 'string|email|max:255|unique:users,email,' . $id . ',userID',
                'tel_number' => 'string|max:20',
                'role' => 'string|in:employee,manager',
                'status' => 'string|in:active,inactive',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            // Handle image update if provided
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($employee->image) {
                    Storage::disk('public')->delete($employee->image);
                }
                
                // Store new image
                $imagePath = $request->file('image')->store('employee_image', 'public');
                $employee->image = $imagePath;
            }
            
            // Update employee fields
            if ($request->has('fullname')) $employee->fullname = $request->fullname;
            if ($request->has('email')) $employee->email = $request->email;
            if ($request->has('tel_number')) $employee->tel_number = $request->tel_number;
            if ($request->has('role')) $employee->role = $request->role;
            if ($request->has('status')) $employee->status = $request->status;
            
            // Update password if provided
            if ($request->has('password') && !empty($request->password)) {
                $employee->password = Hash::make($request->password);
            }
            
            $employee->save();
            
            // Format user image URL in response
            $userData = $employee->toArray();
            if ($employee->image) {
                $userData['image'] = asset('storage/' . $employee->image);
            }
            
            return response()->json([
                'message' => 'Employee updated successfully',
                'employee' => $userData
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating employee: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update employee', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Remove the specified employee
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return response()->json(['message' => 'Unauthorized. Only company admins can delete employees.'], 403);
            }
            
            // Get employee from the same company
            $employee = User::where('userID', $id)
                ->where('companyID', $user->companyID)
                ->where('role', '!=', 'admin')
                ->first();
            
            if (!$employee) {
                return response()->json(['message' => 'Employee not found or not authorized to delete'], 404);
            }
            
            // Delete employee image if exists
            if ($employee->image) {
                Storage::disk('public')->delete($employee->image);
            }
            
            // Delete employee tokens first
            $employee->tokens()->delete();
            
            // Delete employee
            $employee->delete();
            
            return response()->json(['message' => 'Employee deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting employee: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete employee', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Update the status of an employee
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return response()->json(['message' => 'Unauthorized. Only company admins can update employee status.'], 403);
            }
            
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:active,inactive',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
    
            // Get employee from the same company
            $employee = User::where('userID', $id)
                ->where('companyID', $user->companyID)
                ->where('role', '!=', 'admin')
                ->first();
            
            if (!$employee) {
                return response()->json(['message' => 'Employee not found or not authorized to update'], 404);
            }
            
            // Update employee status
            $employee->status = $request->status;
            $employee->save();
            
            return response()->json([
                'message' => 'Employee status updated successfully',
                'employee' => $employee->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating employee status: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update employee status', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get employee statistics for the current admin's company
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllEmployeeStats(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();
            
            // Check if user is admin
            if (!$user->isAdmin()) {
                return response()->json(['message' => 'Unauthorized. Only company admins can access employee stats.'], 403);
            }
            
            // Start with a base query for employees from the same company, excluding the admin
            $baseQuery = User::where('companyID', $user->companyID)
                ->where('userID', '!=', $user->userID)
                ->where('role', '!=', 'admin');
            
            // Apply search filter if provided
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = '%' . $request->search . '%';
                $baseQuery->where(function($q) use ($searchTerm) {
                    $q->where('fullname', 'LIKE', $searchTerm)
                      ->orWhere('email', 'LIKE', $searchTerm)
                      ->orWhere('tel_number', 'LIKE', $searchTerm);
                });
            }
            
            // Apply status filter if provided
            if ($request->has('status') && !empty($request->status)) {
                $baseQuery->where('status', $request->status);
            }
            
            // Clone the base query for each status
            $totalQuery = clone $baseQuery;
            $activeQuery = clone $baseQuery;
            $inactiveQuery = clone $baseQuery;
            
            $stats = [
                'total' => $totalQuery->count(),
                'active' => $activeQuery->where('status', 'active')->count(),
                'inactive' => $inactiveQuery->where('status', 'inactive')->count()
            ];
            
            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Error fetching employee stats: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch employee statistics', 'error' => $e->getMessage()], 500);
        }
    }
}