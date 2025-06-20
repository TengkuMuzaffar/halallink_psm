<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\Item;
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
            $perPage = $request->input('per_page', 10); // Default to 3 items per page
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
                $imagePath = $request->file('company_image')->store('company_image', 'public');
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
    public function show($id, Request $request)
    {
        try {
            $company = Company::with(['admin'])->find($id);
            
            if (!$company) {
                return response()->json(['message' => 'Company not found'], 404);
            }
            
            // Format company image URL
            if ($company->company_image) {
                // Check if the path already contains http:// or https://
                if (strpos($company->company_image, 'http://') === 0 || strpos($company->company_image, 'https://') === 0) {
                    $company->company_image = $company->company_image;
                } else {
                    $company->company_image = asset('storage/' . $company->company_image);
                }
            }
            
            // Load locations with conditional filtering
            $locations = $company->locations();
            
            // Filter out headquarters locations if requested
            if ($request->has('location_type') && $request->location_type === 'no') {
                $locations->where('location_type', '!=', 'headquarters');
            }
            
            // Attach filtered locations to company
            $company->setRelation('locations', $locations->get());
            
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
                $imagePath = $request->file('company_image')->store('company_image', 'public');
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
            Log::info('Starting company deletion process for company ID: ' . $id);
            $company = Company::find($id);
            
            if (!$company) {
                Log::warning('Company not found with ID: ' . $id);
                return response()->json(['message' => 'Company not found'], 404);
            }
            
            Log::info('Found company: ' . $company->company_name . ' (ID: ' . $id . ')');
            
            // Delete company image if exists
            if ($company->company_image) {
                // Check if file exists before attempting to delete
                if (Storage::disk('public')->exists($company->company_image)) {
                    Log::info('Deleting company image: ' . $company->company_image);
                    Storage::disk('public')->delete($company->company_image);
                } else {
                    Log::warning('Company image file not found in storage: ' . $company->company_image);
                }
            }
            
            // Delete associated users
            $users = User::where('companyID', $id)->get();
            Log::info('Found ' . count($users) . ' users associated with company ID: ' . $id);
            
            foreach ($users as $user) {
                Log::info('Processing user: ' . $user->fullname . ' (ID: ' . $user->userID . ')');
                
                // First, handle items associated with this user
                $items = Item::where('userID', $user->userID)->get();
                Log::info('Found ' . count($items) . ' items associated with user ID: ' . $user->userID);
                
                foreach ($items as $item) {
                    // First check if there are any carts/orders associated with this item
                    $carts = Cart::where('itemID', $item->itemID)->get();
                    Log::info('Found ' . count($carts) . ' carts associated with item ID: ' . $item->itemID);
                    
                    // Delete all carts associated with this item first
                    foreach ($carts as $cart) {
                        Log::info('Deleting cart ID: ' . $cart->cartID);
                        $cart->delete();
                    }
                    
                    Log::info('Deleting item ID: ' . $item->itemID);
                    // Use forceDelete if you want to bypass soft delete
                    $item->delete(); // Or use forceDelete() if needed
                }
                
                // Delete user image if exists
                if ($user->image) {
                    // Check if file exists before attempting to delete
                    if (Storage::disk('public')->exists($user->image)) {
                        Log::info('Deleting user image: ' . $user->image);
                        Storage::disk('public')->delete($user->image);
                    } else {
                        Log::warning('User image file not found in storage: ' . $user->image);
                    }
                }
                
                // Delete user tokens
                $tokenCount = $user->tokens()->count();
                Log::info('Deleting ' . $tokenCount . ' tokens for user ID: ' . $user->userID);
                $user->tokens()->delete();
                
                // Delete user
                Log::info('Deleting user ID: ' . $user->userID);
                $user->delete();
            }
            
            // Delete company
            Log::info('Deleting company ID: ' . $id);
            $company->delete();
            
            Log::info('Company deletion completed successfully for ID: ' . $id);
            return response()->json(['message' => 'Company and associated users deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting company ID ' . $id . ': ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to delete company', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get items by location ID
     *
     * @param  int  $locationId
     * @return \Illuminate\Http\Response
     */
    public function getItemsByLocation(Request $request, $locationId)
    {
        try {
            // Validate location exists
            $location = \App\Models\Location::find($locationId);
            
            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location not found'
                ], 404);
            }
            
            // Start with a base query for items associated with this location
            $query = \App\Models\Item::where(function($q) use ($locationId) {
                $q->where('locationID', $locationId)
                  ->orWhere('slaughterhouse_locationID', $locationId);
            });
            
            // Apply search filter if provided
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->whereHas('poultry', function($poultryQuery) use ($searchTerm) {
                        $poultryQuery->where('poultry_name', 'LIKE', $searchTerm);
                    });
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);
            
            // Get items with relationships
            $query->with(['poultry', 'user', 'location', 'slaughterhouse']);
            
            // Paginate results
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $paginatedItems = $query->paginate($perPage);
            
            // Format the response
            return response()->json([
                'success' => true,
                'data' => $paginatedItems->items(),
                'pagination' => [
                    'current_page' => $paginatedItems->currentPage(),
                    'last_page' => $paginatedItems->lastPage(),
                    'per_page' => $paginatedItems->perPage(),
                    'total' => $paginatedItems->total(),
                    'from' => $paginatedItems->firstItem(),
                    'to' => $paginatedItems->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching items by location: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch items',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tasks by location ID
     *
     * @param  int  $locationId
     * @return \Illuminate\Http\Response
     */
    public function getTasksByLocation(Request $request, $locationId)
    {
        try {
            // Validate location exists
            $location = \App\Models\Location::find($locationId);
            
            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location not found'
                ], 404);
            }
            
            // Get checkpoints with arrange_number 2 for this location
            $query = \App\Models\Checkpoint::where('locationID', $locationId)
                ->where('arrange_number', 2)
                ->with(['task' => function($query) {
                    $query->select('taskID', 'checkID', 'task_type', 'task_status', 'start_timestamp', 'finish_timestamp');
                }, 'verifies' => function($query) {
                    $query->select('verifyID', 'checkID', 'verify_status');
                }]);
            
            // Apply search filter if provided
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->whereHas('task', function($taskQuery) use ($searchTerm) {
                        $taskQuery->where('task_type', 'LIKE', $searchTerm)
                                 ->orWhere('task_status', 'LIKE', $searchTerm);
                    });
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);
            
            // Paginate results
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $paginatedTasks = $query->paginate($perPage);
            
            // Process the results to add custom status based on verification and task timestamps
            $processedData = collect($paginatedTasks->items())->map(function($checkpoint) {
                // Create a new object with the checkpoint data
                $result = $checkpoint->toArray();
                
                // Determine the task status based on verification and timestamps
                $verifies = $checkpoint->verifies;
                $task = $checkpoint->task;
                
                if ($verifies->isEmpty()) {
                    // If no verification exists, task is not delivered yet
                    $result['custom_status'] = 'not delivered';
                } else {
                    $verify = $verifies->first(); // Get the first verification
                    
                    if ($verify->verify_status != 'complete') {
                        // If verification status is not complete, task is in progress
                        $result['custom_status'] = 'in progress';
                    } else if ($verify->verify_status == 'complete') {
                        if (!$task || $task->start_timestamp === null) {
                            // If verification is complete but task hasn't started, it's delivered
                            $result['custom_status'] = 'delivered';
                        } else if ($task->start_timestamp !== null && $task->finish_timestamp === null) {
                            // If task has started but not finished, it's in progress
                            $result['custom_status'] = 'task started';
                        } else if ($task->start_timestamp !== null && $task->finish_timestamp !== null) {
                            // If task has started and finished, it's complete
                            $result['custom_status'] = 'task complete';
                        }
                    }
                }
                
                return $result;
            });
            
            // Format the response
            return response()->json([
                'success' => true,
                'data' => $processedData,
                'pagination' => [
                    'current_page' => $paginatedTasks->currentPage(),
                    'last_page' => $paginatedTasks->lastPage(),
                    'per_page' => $paginatedTasks->perPage(),
                    'total' => $paginatedTasks->total(),
                    'from' => $paginatedTasks->firstItem(),
                    'to' => $paginatedTasks->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching tasks by location: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get certifications for a company
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getCompanyCertifications($id)
    {
        try {
            $company = Company::find($id);
            
            if (!$company) {
                return response()->json(['message' => 'Company not found'], 404);
            }
            
            // Get certifications with file URLs
            $certifications = $company->certs()->get()->map(function($cert) {
                return [
                    'certID' => $cert->certID,
                    'companyID' => $cert->companyID,
                    'cert_type' => $cert->cert_type,
                    'cert_file' => $cert->cert_file ? asset('storage/' . $cert->cert_file) : null,
                    'created_at' => $cert->created_at,
                    'updated_at' => $cert->updated_at
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $certifications
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching company certifications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch company certifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get orders for a company
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
        public function getCompanyOrders(Request $request, $id)
    {
        try {
            // Find the company
            $company = Company::find($id);
            
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found'
                ], 404);
            }
            
            // Get pagination parameters
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            
            // Initialize query based on company type
            if ($company->company_type === 'broiler') {
                // For broiler companies, get all items belonging to the company
                // through users associated with the company
                $userIds = User::where('companyID', $id)->pluck('userID')->toArray();
                
                // Get all items created by these users
                $itemIds = Item::whereIn('userID', $userIds)->pluck('itemID')->toArray();
                
                // Get all cart items related to these items
                $query = \App\Models\Cart::whereIn('itemID', $itemIds)
                    ->with(['item.poultry', 'order'])
                    ->whereHas('order', function($q) {
                        $q->whereNotNull('order_timestamp');
                    })
                    ->join('orders', 'carts.orderID', '=', 'orders.orderID')
                    ->orderBy('orders.order_timestamp', 'desc');
                    
            } else if ($company->company_type === 'sme') {
                // For SME companies, get orders based on locationID if provided
                $locationId = $request->input('locationID');
                
                $query = \App\Models\Order::where(function($q) use ($id, $locationId) {
                    // Orders must be associated with this company
                    $q->whereHas('user', function($userQuery) use ($id) {
                        $userQuery->where('companyID', $id);
                    });
                    
                    // Apply location filter if provided
                    if ($locationId) {
                        $q->where('locationID', $locationId);
                    }
                })
                ->with(['payment', 'cartItems'])
                ->orderBy('order_timestamp', 'desc');
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Company type not supported for order retrieval'
                ], 400);
            }
            
            // Apply pagination
            $paginatedResults = $query->paginate($perPage, ['*'], 'page', $page);
            
            // Format the response with proper image URLs
            $formattedData = collect($paginatedResults->items())->map(function($item) {
                // For broiler companies (Cart items)
                if (isset($item->item)) {
                    // Process item image URL on the server side
                    if (isset($item->item->item_image)) {
                        if ($item->item->item_image) {
                            // Check if the path already contains http:// or https://
                            if (strpos($item->item->item_image, 'http://') === 0 || strpos($item->item->item_image, 'https://') === 0) {
                                $item->item->item_image = $item->item->item_image;
                            } else {
                                $item->item->item_image = asset('storage/' . $item->item->item_image);
                            }
                        } else {
                            $item->item->item_image = null;
                        }
                    }
                    
                    // Process poultry image if available
                    if (isset($item->item->poultry) && isset($item->item->poultry->poultry_image)) {
                        if ($item->item->poultry->poultry_image) {
                            // Check if the path already contains http:// or https://
                            if (strpos($item->item->poultry->poultry_image, 'http://') === 0 || strpos($item->item->poultry->poultry_image, 'https://') === 0) {
                                $item->item->poultry->poultry_image = $item->item->poultry->poultry_image;
                            } else {
                                $item->item->poultry->poultry_image = asset('storage/' . $item->item->poultry->poultry_image);
                            }
                        } else {
                            $item->item->poultry->poultry_image = null;
                        }
                    }
                }
                
                // For SME companies (Orders with cart items)
                if (isset($item->cartItems)) {
                    $item->cartItems->transform(function($cartItem) {
                        if (isset($cartItem->item) && isset($cartItem->item->item_image)) {
                            if ($cartItem->item->item_image) {
                                // Check if the path already contains http:// or https://
                                if (strpos($cartItem->item->item_image, 'http://') === 0 || strpos($cartItem->item->item_image, 'https://') === 0) {
                                    $cartItem->item->item_image = $cartItem->item->item_image;
                                } else {
                                    $cartItem->item->item_image = asset('storage/' . $cartItem->item->item_image);
                                }
                            } else {
                                $cartItem->item->item_image = null;
                            }
                        }
                        
                        if (isset($cartItem->item) && isset($cartItem->item->poultry) && isset($cartItem->item->poultry->poultry_image)) {
                            if ($cartItem->item->poultry->poultry_image) {
                                // Check if the path already contains http:// or https://
                                if (strpos($cartItem->item->poultry->poultry_image, 'http://') === 0 || strpos($cartItem->item->poultry->poultry_image, 'https://') === 0) {
                                    $cartItem->item->poultry->poultry_image = $cartItem->item->poultry->poultry_image;
                                } else {
                                    $cartItem->item->poultry->poultry_image = asset('storage/' . $cartItem->item->poultry->poultry_image);
                                }
                            } else {
                                $cartItem->item->poultry->poultry_image = null;
                            }
                        }
                        
                        return $cartItem;
                    });
                }
                
                return $item;
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'pagination' => [
                    'current_page' => $paginatedResults->currentPage(),
                    'last_page' => $paginatedResults->lastPage(),
                    'per_page' => $paginatedResults->perPage(),
                    'total' => $paginatedResults->total(),
                    'from' => $paginatedResults->firstItem(),
                    'to' => $paginatedResults->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching company orders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch company orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Get a specific order by ID with its items
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @param  int  $orderId
     * @return \Illuminate\Http\Response
     */
    public function getOrderDetails(Request $request, $id, $orderId)
    {
        try {
            // Find the company
            $company = Company::findOrFail($id);
            
            // Find the order and make sure it belongs to this company
            $order = \App\Models\Order::with(['payment', 'cartItems.item.poultry'])
                ->where('orderID', $orderId)
                ->whereHas('location', function($query) use ($company) {
                    $query->where('companyID', $company->companyID);
                })
                ->firstOrFail();
            
            // Format the response
            $formattedOrder = [
                'orderID' => $order->orderID,
                'userID' => $order->userID,
                'locationID' => $order->locationID,
                'paymentID' => $order->paymentID,
                'order_timestamp' => $order->order_timestamp,
                'deliver_timestamp' => $order->deliver_timestamp,
                'order_status' => $order->order_status,
                'payment' => $order->payment,
                'items' => []
            ];
            
            // Format cart items with their associated items and poultry
            foreach ($order->cartItems as $cartItem) {
                $item = [
                    'cartID' => $cartItem->cartID,
                    'itemID' => $cartItem->itemID,
                    'orderID' => $cartItem->orderID,
                    'quantity' => $cartItem->quantity,
                    'price_at_purchase' => $cartItem->price_at_purchase,
                    'item_cart_delivered' => $cartItem->item_cart_delivered,
                    'item' => []
                ];
                
                if ($cartItem->item) {
                    $itemData = [
                        'itemID' => $cartItem->item->itemID,
                        'poultryID' => $cartItem->item->poultryID,
                        'userID' => $cartItem->item->userID,
                        'locationID' => $cartItem->item->locationID,
                        'measurement_type' => $cartItem->item->measurement_type,
                        'measurement_value' => $cartItem->item->measurement_value,
                        'price' => $cartItem->item->price,
                        'stock' => $cartItem->item->stock,
                    ];
                    
                    // Add item image with full URL
                    if ($cartItem->item->item_image) {
                        $itemData['item_image'] = asset('storage/' . $cartItem->item->item_image);
                    }
                    
                    // // Add poultry data if available
                    // if ($cartItem->item->poultry) {
                    //     $poultryData = [
                    //         'poultryID' => $cartItem->item->poultry->poultryID,
                    //         'poultry_name' => $cartItem->item->poultry->poultry_name,
                    //     ];
                        
                    //     // Add poultry image with full URL
                    //     if ($cartItem->item->poultry->poultry_image) {
                    //         $poultryData['poultry_image'] = asset('storage/' . $cartItem->item->poultry->poultry_image);
                    //     }
                        
                    //     $itemData['poultry'] = $poultryData;
                    // }
                    
                    $item['item'] = $itemData;
                }
                
                $formattedOrder['items'][] = $item;
            }
            
            return response()->json([
                'success' => true,
                'data' => $formattedOrder
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or does not belong to this company'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching order details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}