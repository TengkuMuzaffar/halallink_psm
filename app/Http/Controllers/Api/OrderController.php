<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Location;
use App\Models\Item;
use App\Models\Cart;
use App\Models\User;
use App\Models\Company;
use App\Models\Checkpoint;
use App\Models\Verify;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the orders.
     */
    public function index(Request $request)
    {
        try {
            // Get authenticated user and company
            $user = Auth::user();
            $companyID = $user->companyID;
            
            // Get company information
            $company = Company::find($companyID);
            if (!$company) {
                return response()->json(['message' => 'Company not found'], 404);
            }
            
            // Get global pagination parameters
            $perPage = $request->input('per_page', 10);
            
            // Get location-specific pagination parameters if provided
            $locationPages = $request->input('location_pages', []);
            
            // Get locations belonging to the company
            $locationsQuery = Location::where('companyID', $companyID);
            
            // Apply search filter if provided (search by company_name, company_address, or orderID)
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = '%' . $request->search . '%';
                $locationsQuery->where(function($q) use ($searchTerm) {
                    $q->where('company_address', 'LIKE', $searchTerm)
                      ->orWhereHas('company', function($query) use ($searchTerm) {
                          $query->where('company_name', 'LIKE', $searchTerm);
                      })
                      // Add search by orderID
                      ->orWhereHas('checkpoints.order', function($query) use ($searchTerm) {
                          // Remove the % wildcards for exact orderID matching
                          $exactSearchTerm = trim($searchTerm, '%');
                          $query->where('orderID', $exactSearchTerm);
                      });
                });
            }
            
            $locations = $locationsQuery->get();
            $locationIDs = $locations->pluck('locationID')->toArray();
            
            // Initialize array for organizing data
            $groupedData = [];
            $paginationData = [];
            
            // Process each location
            foreach ($locations as $location) {
                $locationID = $location->locationID;
                
                // Get checkpoints for this location
                $checkpoints = Checkpoint::where('locationID', $locationID)
                    ->with(['order', 'verifies'])
                    ->get();
                
                // Skip if no checkpoints found
                if ($checkpoints->isEmpty()) {
                    continue;
                }
                
                // Initialize location data structure
                $groupedData[$locationID] = [
                    'locationID' => $locationID,
                    'companyID' => $location->companyID,
                    'company_address' => $location->company_address ?? 'Unknown Location',
                    'company_name' => $location->company_name ?? '',
                    'location_type' => $location->location_type ?? 'Unknown Type',
                    'orders' => []
                ];
                
                // Collect all order IDs for this location
                $orderIDs = $checkpoints->pluck('orderID')->filter()->unique()->values()->toArray();
                
                // Apply date range filters if provided
                $filteredOrderIDs = $orderIDs;
                if (!empty($orderIDs) && ($request->has('date_from') || $request->has('date_to'))) {
                    $orderQuery = Order::whereIn('orderID', $orderIDs);
                    
                    if ($request->has('date_from') && !empty($request->date_from)) {
                        $orderQuery->whereDate('created_at', '>=', $request->date_from);
                    }
                    
                    if ($request->has('date_to') && !empty($request->date_to)) {
                        $orderQuery->whereDate('created_at', '<=', $request->date_to);
                    }
                    
                    $filteredOrderIDs = $orderQuery->pluck('orderID')->toArray();
                }
                
                // If no orders match the date filter, skip this location
                if (empty($filteredOrderIDs)) {
                    continue;
                }
                
                // Preload all cart items for these orders to reduce database queries
                $cartItemsByOrder = [];
                if (!empty($filteredOrderIDs)) {
                    $allCartItems = Cart::whereIn('orderID', $filteredOrderIDs)
                        ->with(['item.poultry', 'item.location', 'item.slaughterhouse'])
                        ->get();
                    
                    // Group cart items by order ID
                    foreach ($allCartItems as $cartItem) {
                        if (!isset($cartItemsByOrder[$cartItem->orderID])) {
                            $cartItemsByOrder[$cartItem->orderID] = [];
                        }
                        $cartItemsByOrder[$cartItem->orderID][] = $cartItem;
                    }
                }
                
                // Group checkpoints by order
                $orderCheckpoints = [];
                foreach ($checkpoints as $checkpoint) {
                    if (!$checkpoint->orderID || !in_array($checkpoint->orderID, $filteredOrderIDs)) continue;
                    
                    $orderID = $checkpoint->orderID;
                    if (!isset($orderCheckpoints[$orderID])) {
                        $orderCheckpoints[$orderID] = [];
                    }
                    
                    $orderCheckpoints[$orderID][] = $checkpoint;
                }
                
                // Process each order
                $allOrdersForLocation = [];
                foreach ($orderCheckpoints as $orderID => $checkpointList) {
                    $order = Order::with(['user'])->find($orderID);
                    if (!$order) continue;
                    
                    // Initialize order data
                    $orderData = [
                        'orderID' => $orderID,
                        'order_status' => $order->order_status,
                        'created_at' => $order->created_at,
                        'user' => $order->user,
                        'items' => [] // New array to store all items at order level
                    ];
                    
                    // Process all items for this order once
                    $orderItems = [];
                    $itemDetailsById = [];
                    
                    if (isset($cartItemsByOrder[$orderID])) {
                        foreach ($cartItemsByOrder[$orderID] as $cartItem) {
                            $item = $cartItem->item;
                            
                            if ($item) {
                                $itemDetails = [
                                    'itemID' => $item->itemID,
                                    'cartID' => $cartItem->cartID,
                                    'item_cart_delivered' => $cartItem->item_cart_delivered,
                                    'item_name' => $item->poultry ? $item->poultry->poultry_name : 'Unknown',
                                    'measurement_type' => $item->measurement_type,
                                    'measurement_value' => $item->measurement_value,
                                    'price' => $item->price,
                                    'quantity' => $cartItem->quantity,
                                    'price_at_purchase' => $cartItem->price_at_purchase,
                                    'total_price' => $cartItem->price_at_purchase * $cartItem->quantity,
                                    'supplier_locationID' => $item->locationID,
                                    'supplier_location_address' => $item->location ? $item->location->company_address : 'Unknown',
                                    'slaughterhouse_locationID' => $item->slaughterhouse_locationID,
                                    'slaughterhouse_location_address' => $item->slaughterhouse ? $item->slaughterhouse->company_address : 'N/A'
                                ];
                                
                                $orderData['items'][] = $itemDetails;
                                $itemDetailsById[$item->itemID] = $itemDetails;
                            }
                        }
                    }
                    
                    // Initialize status tracking variables
                    $hasNoVerification = false;
                    $hasIncompleteVerification = false;
                    $allComplete = true;
                    
                    // Process checkpoints for this order
                    foreach ($checkpointList as $checkpoint) {
                        // Skip checkpoint with arrange_number 2 if its task is complete
                        // or skip checkpoint with arrange_number 3 if the task for arrange_number 2 is pending
                        if ($checkpoint->arrange_number == 2 || $checkpoint->arrange_number == 3) {
                            // Find the checkpoint with arrange_number 2 for this order
                            $checkpoint2 = null;
                            foreach ($checkpointList as $cp) {
                                if ($cp->arrange_number == 2) {
                                    $checkpoint2 = $cp;
                                    break;
                                }
                            }
                            
                            if ($checkpoint2) {
                                // Get task for checkpoint with arrange_number 2
                                $task = Task::where('checkID', $checkpoint2->checkID)->first();
                                
                                // Skip checkpoint 2 if its task is complete
                                if ($checkpoint->arrange_number == 2 && $task && $task->task_status == 'complete') {
                                    continue;
                                }
                                
                                // Skip checkpoint 3 if the task for checkpoint 2 is pending
                                if ($checkpoint->arrange_number == 3 && $task && $task->task_status == 'pending') {
                                    continue;
                                }
                            }
                        }
                        
                        // Determine checkpoint status based on verifies
                        $checkpointStatus = 'pending';
                        
                        if (!$checkpoint->verifies || $checkpoint->verifies->isEmpty()) {
                            $checkpointStatus = 'pending';
                            $hasNoVerification = true;
                            $allComplete = false;
                        } else {
                            $hasIncomplete = false;
                            foreach ($checkpoint->verifies as $verify) {
                                if ($verify->verify_status !== 'complete') {
                                    $hasIncomplete = true;
                                    $hasIncompleteVerification = true;
                                    $allComplete = false;
                                    break;
                                }
                            }
                            
                            if ($hasIncomplete) {
                                $checkpointStatus = 'processing';
                            } else {
                                $checkpointStatus = 'complete';
                            }
                        }
                        
                        // Get item IDs from checkpoint's item_record
                        $checkpointItemIds = [];
                        if ($checkpoint->item_record) {
                            $itemIDs = is_array($checkpoint->item_record) 
                                ? $checkpoint->item_record 
                                : json_decode($checkpoint->item_record, true);
                            
                            if (is_array($itemIDs)) {
                                $checkpointItemIds = $itemIDs;
                            }
                        }
                    }
                    
                    // Calculate order status based on the new requirements
                    if ($hasNoVerification) {
                        $orderStatus = 'waiting_for_delivery'; // Status 1: waiting for delivery
                    } else if ($hasIncompleteVerification) {
                        $orderStatus = 'in_progress'; // Status 2: in progress
                    } else if ($allComplete) {
                        $orderStatus = 'complete'; // Status 3: complete
                    } else {
                        $orderStatus = 'waiting_for_delivery'; // Default to waiting for delivery
                    }
                    
                    $orderData['calculated_status'] = $orderStatus;
                    
                    // Apply status filter if provided
                    if ($request->has('status') && !empty($request->status)) {
                        if ($orderStatus !== $request->status) {
                            continue; // Skip this order if it doesn't match the status filter
                        }
                    }
                    
                    // Add order to the location's orders array
                    $allOrdersForLocation[] = $orderData;
                }
                
                // Get the current page for this location
                $currentPage = isset($locationPages[$locationID]) ? (int)$locationPages[$locationID] : 1;
                if ($currentPage < 1) $currentPage = 1;
                
                // Calculate pagination for this location
                $totalOrders = count($allOrdersForLocation);
                $lastPage = ceil($totalOrders / $perPage);
                if ($lastPage < 1) $lastPage = 1;
                
                if ($currentPage > $lastPage) $currentPage = $lastPage;
                
                // Paginate orders for this location
                $paginatedOrders = array_slice($allOrdersForLocation, ($currentPage - 1) * $perPage, $perPage);
                
                // Store pagination data for this location
                $paginationData[$locationID] = [
                    'total' => $totalOrders,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'from' => $totalOrders > 0 ? (($currentPage - 1) * $perPage) + 1 : 0,
                    'to' => min($currentPage * $perPage, $totalOrders)
                ];
                
                // Add paginated orders to the location data
                $groupedData[$locationID]['orders'] = $paginatedOrders;
                $groupedData[$locationID]['pagination'] = $paginationData[$locationID];
                
                // Remove locations with no orders after filtering
                if (empty($groupedData[$locationID]['orders'])) {
                    unset($groupedData[$locationID]);
                }
            }
            
            // Convert to array
            $locationArray = array_values($groupedData);
            
            return response()->json([
                'success' => true,
                'data' => $locationArray
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created order in storage.
     * (This might be complex depending on your cart/checkout flow)
     * For now, a basic placeholder.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.itemID' => 'required|exists:items,itemID',
            'items.*.quantity' => 'required|integer|min:1',
            'delivery_address' => 'required|string|max:255',
            // Add other necessary fields like total_amount, payment details etc.
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // This is a simplified store method. 
        // In a real app, this would likely be handled after a payment process.
        // For now, assuming order creation is direct.

        try {
            DB::beginTransaction();

            $order = Order::create([
                'userID' => $user->userID,
                'companyID' => $user->companyID, // Assuming orders are tied to the user's company
                'order_status' => 'pending', // Default status
                'total_amount' => $request->total_amount, // Calculate this based on items
                'delivery_address' => $request->delivery_address,
                // ... other fields
            ]);

            // Attach items to order (assuming an order_items pivot table or similar)
            // This depends on your Order model's relationships (e.g., items())
            // foreach ($request->items as $itemData) {
            //    $order->items()->attach($itemData['itemID'], ['quantity' => $itemData['quantity']]);
            // }

            DB::commit();
            return response()->json($order->load(['user', 'items']), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified order.
     */
    public function show($id)
    {
        $user = Auth::user();
        $order = Order::with(['user', 'items.poultry', 'items.company', 'payment', 'checkpoints.tasks', 'checkpoints.location', 'delivery.vehicle'])
                      ->where('companyID', $user->companyID) // Ensure user can only see their company's orders
                      ->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        
        // Further process to structure item_records with locations if needed
        // This part is highly dependent on how `item_record` and locations are structured
        // and related to orders or items within orders.
        // For now, we return the order with its relations.
        // You might need a custom resource or transformer here.

        return response()->json($order);
    }

    /**
     * Update the specified order in storage.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $order = Order::where('companyID', $user->companyID)->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'order_status' => 'sometimes|string|in:pending,processing,shipped,delivered,completed,cancelled',
            'delivery_address' => 'sometimes|string|max:255',
            // Add other updatable fields
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order->update($request->only(['order_status', 'delivery_address'])); // Add other fields

        return response()->json($order->fresh()->load(['user', 'items', 'payment']));
    }
    
    /**
     * Get order statistics
     */
    public function getStats(Request $request)
    {
        try {
            $user = Auth::user();
            $companyID = $user->companyID;
            
            // Get company type
            $company = Company::find($companyID);
            if (!$company) {
                return response()->json(['message' => 'Company not found'], 404);
            }
            
            $companyType = strtolower($company->company_type);
            
            // Different logic based on company type
            if ($companyType === 'broiler') {
                // For broiler companies: Find orders through carts -> items -> user -> companyID
                $baseQuery = Order::whereIn('orderID', function($subQuery) use ($companyID) {
                    $subQuery->select('carts.orderID')
                        ->from('carts')
                        ->join('items', 'carts.itemID', '=', 'items.itemID')
                        ->join('users', 'items.userID', '=', 'users.userID')
                        ->where('users.companyID', $companyID)
                        ->distinct(); // Ensure unique orderIDs
                });
            } else {
                // For SME and other company types: Use the userID from the company instead of companyID
                // Get all users belonging to this company
                $companyUserIds = User::where('companyID', $companyID)->pluck('userID')->toArray();
                
                // Find orders created by any user in this company
                $baseQuery = Order::whereIn('userID', $companyUserIds);
            }
            
            // Apply date range filters if provided
            if ($request->has('date_from') && !empty($request->date_from)) {
                $baseQuery->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to') && !empty($request->date_to)) {
                $baseQuery->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Calculate total orders
            $total_orders = (clone $baseQuery)->count();
            
            // Calculate waiting for delivery orders
            $waiting_for_delivery_orders = (clone $baseQuery)->where('order_status', 'waiting_for_delivery')->count();
            
            // Calculate in progress orders
            $in_progress_orders = (clone $baseQuery)->where('order_status', 'in_progress')->count();
            
            // Calculate complete orders
            $complete_orders = (clone $baseQuery)->where('order_status', 'complete')->count();
            
            // Return the statistics
            return response()->json([
                'total_orders' => $total_orders,
                'waiting_for_delivery_orders' => $waiting_for_delivery_orders,
                'in_progress_orders' => $in_progress_orders,
                'complete_orders' => $complete_orders
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching order statistics: ' . $e->getMessage()], 500);
        }
    }


// Add this new method to OrderController
public function getLocationsByCompanyType(Request $request)
{
    try {
        $user = Auth::user();
        $companyID = $user->companyID;
        
        // Get company information
        $company = Company::find($companyID);
        if (!$company) {
            return response()->json(['message' => 'Company not found'], 404);
        }
        
        // Determine location type based on company type
        $locationType = null;
        switch ($company->company_type) {
            case 'broiler':
                $locationType = 'supplier';
                break;
            case 'sme':
                $locationType = 'kitchen';
                break;
            case 'slaughterhouse':
                $locationType = 'slaughterhouse';
                break;
            default:
                // If company type doesn't match, return all locations for this company
                break;
        }
        
        // Build locations query
        $locationsQuery = Location::where('companyID', $companyID);
        
        // Filter by location type if determined
        if ($locationType) {
            $locationsQuery->where('location_type', $locationType);
        }
        
        // Apply search filter if provided
        // if ($request->has('search') && !empty($request->search)) {
        //     $searchTerm = '%' . $request->search . '%';
        //     $locationsQuery->where(function($q) use ($searchTerm) {
        //         $q->where('company_address', 'LIKE', $searchTerm)
        //           ->orWhereHas('company', function($query) use ($searchTerm) {
        //               $query->where('company_name', 'LIKE', $searchTerm);
        //           })
        //           ->orWhereHas('checkpoints.order', function($query) use ($searchTerm) {
        //               $exactSearchTerm = trim($searchTerm, '%');
        //               $query->where('orderID', $exactSearchTerm);
        //           });
        //     });
        // }
        
        $locations = $locationsQuery->get();
        $locationData = [];
        
        foreach ($locations as $location) {
            $locationID = $location->locationID;
            
            // Get order count for this location with basic filters
            $orderCountQuery = Order::whereHas('checkpoints', function($query) use ($locationID) {
                $query->where('locationID', $locationID);
            });
            
            // // Apply date range filters if provided
            // if ($request->has('date_from') && !empty($request->date_from)) {
            //     $orderCountQuery->whereDate('created_at', '>=', $request->date_from);
            // }
            
            // if ($request->has('date_to') && !empty($request->date_to)) {
            //     $orderCountQuery->whereDate('created_at', '<=', $request->date_to);
            // }
            
            $orderCount = $orderCountQuery->count();
            
            // Only include locations that have orders
            if ($orderCount > 0) {
                $locationData[] = [
                    'locationID' => $locationID,
                    'companyID' => $location->companyID,
                    'company_address' => $location->company_address ?? 'Unknown Location',
                    'company_name' => $location->company->company_name ?? '',
                    'location_type' => $location->location_type ?? 'Unknown Type',
                    'order_count' => $orderCount
                ];
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => $locationData,
            'company_type' => $company->company_type,
            'filtered_location_type' => $locationType
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error fetching locations: ' . $e->getMessage()
        ], 500);
    }
}
// Add this new method to fetch orders for a specific location
public function getOrdersByLocationID(Request $request, $locationID)
{
    try {
        $user = Auth::user();
        $companyID = $user->companyID;
        
        // Verify location belongs to the company
        $location = Location::where('locationID', $locationID)
                           ->where('companyID', $companyID)
                           ->first();
        
        if (!$location) {
            return response()->json(['message' => 'Location not found or access denied'], 404);
        }
        
        $perPage = $request->input('per_page', 10);
        $currentPage = $request->input('page', 1);
        
        // Get checkpoints for this location
        $checkpoints = Checkpoint::where('locationID', $locationID)
            ->with(['order', 'verifies'])
            ->get();
            
        if ($checkpoints->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => [],
                    'pagination' => [
                        'total' => 0,
                        'per_page' => $perPage,
                        'current_page' => $currentPage,
                        'last_page' => 1,
                        'from' => 0,
                        'to' => 0
                    ]
                ]
            ]);
        }
        
        // Collect all order IDs for this location
        $orderIDs = $checkpoints->pluck('orderID')->filter()->unique()->values()->toArray();
        
        // Apply filters
        $filteredOrderIDs = $orderIDs;
        if (!empty($orderIDs)) {
            $orderQuery = Order::whereIn('orderID', $orderIDs);
            
            // Apply date range filters
            if ($request->has('date_from') && !empty($request->date_from)) {
                $orderQuery->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to') && !empty($request->date_to)) {
                $orderQuery->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Add search filter for order ID or user information
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $orderQuery->where(function($query) use ($searchTerm) {
                    $query->where('orderID', 'like', '%' . $searchTerm . '%')
                          ->orWhereHas('user', function($userQuery) use ($searchTerm) {
                              $userQuery->where('name', 'like', '%' . $searchTerm . '%')
                                       ->orWhere('email', 'like', '%' . $searchTerm . '%');
                          });
                });
            }
            
            // Add order status filter (from order table)
            if ($request->has('order_status') && !empty($request->order_status)) {
                $orderQuery->where('order_status', $request->order_status);
            }
            
            // Add payment status filter
            if ($request->has('payment_status') && !empty($request->payment_status)) {
                $orderQuery->where('payment_status', $request->payment_status);
            }
            
            // Add date range for specific date field
            if ($request->has('delivery_date_from') && !empty($request->delivery_date_from)) {
                $orderQuery->whereDate('delivery_date', '>=', $request->delivery_date_from);
            }
            
            if ($request->has('delivery_date_to') && !empty($request->delivery_date_to)) {
                $orderQuery->whereDate('delivery_date', '<=', $request->delivery_date_to);
            }
            
            $filteredOrderIDs = $orderQuery->pluck('orderID')->toArray();
        }
        
        if (empty($filteredOrderIDs)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'orders' => [],
                    'pagination' => [
                        'total' => 0,
                        'per_page' => $perPage,
                        'current_page' => $currentPage,
                        'last_page' => 1,
                        'from' => 0,
                        'to' => 0
                    ]
                ]
            ]);
        }
        
        // Preload all cart items for these orders
        $cartItemsByOrder = [];
        if (!empty($filteredOrderIDs)) {
            $allCartItems = Cart::whereIn('orderID', $filteredOrderIDs)
                ->with(['item.poultry', 'item.location', 'item.slaughterhouse'])
                ->get();
            
            foreach ($allCartItems as $cartItem) {
                if (!isset($cartItemsByOrder[$cartItem->orderID])) {
                    $cartItemsByOrder[$cartItem->orderID] = [];
                }
                $cartItemsByOrder[$cartItem->orderID][] = $cartItem;
            }
        }
        
        // Group checkpoints by order
        $orderCheckpoints = [];
        foreach ($checkpoints as $checkpoint) {
            if (!$checkpoint->orderID || !in_array($checkpoint->orderID, $filteredOrderIDs)) continue;
            
            $orderID = $checkpoint->orderID;
            if (!isset($orderCheckpoints[$orderID])) {
                $orderCheckpoints[$orderID] = [];
            }
            
            $orderCheckpoints[$orderID][] = $checkpoint;
        }
        
        // Process each order (reuse existing logic from the original method)
        $allOrdersForLocation = [];
        foreach ($orderCheckpoints as $orderID => $checkpointList) {
            $order = Order::with(['user'])->find($orderID);
            if (!$order) continue;
            
            // Initialize order data
            $orderData = [
                'orderID' => $orderID,
                'order_status' => $order->order_status,
                'created_at' => $order->created_at,
                'user' => $order->user,
                'items' => []
            ];
            
            // Process items
            if (isset($cartItemsByOrder[$orderID])) {
                foreach ($cartItemsByOrder[$orderID] as $cartItem) {
                    $item = $cartItem->item;
                    
                    if ($item) {
                        $itemDetails = [
                            'itemID' => $item->itemID,
                            'cartID' => $cartItem->cartID,
                            'item_cart_delivered' => $cartItem->item_cart_delivered,
                            'item_name' => $item->poultry ? $item->poultry->poultry_name : 'Unknown',
                            'measurement_type' => $item->measurement_type,
                            'measurement_value' => $item->measurement_value,
                            'price' => $item->price,
                            'quantity' => $cartItem->quantity,
                            'price_at_purchase' => $cartItem->price_at_purchase,
                            'total_price' => $cartItem->price_at_purchase * $cartItem->quantity,
                            'supplier_locationID' => $item->locationID,
                            'supplier_location_address' => $item->location ? $item->location->company_address : 'Unknown',
                            'slaughterhouse_locationID' => $item->slaughterhouse_locationID,
                            'slaughterhouse_location_address' => $item->slaughterhouse ? $item->slaughterhouse->company_address : 'N/A'
                        ];
                        
                        $orderData['items'][] = $itemDetails;
                    }
                }
            }
            
            // Process checkpoints and calculate status (reuse existing logic)
            $hasNoVerification = false;
            $hasIncompleteVerification = false;
            $allComplete = true;
            
            foreach ($checkpointList as $checkpoint) {
                // Skip checkpoint logic (same as original)
                if ($checkpoint->arrange_number == 2 || $checkpoint->arrange_number == 3) {
                    $checkpoint2 = null;
                    foreach ($checkpointList as $cp) {
                        if ($cp->arrange_number == 2) {
                            $checkpoint2 = $cp;
                            break;
                        }
                    }
                    
                    if ($checkpoint2) {
                        $task = Task::where('checkID', $checkpoint2->checkID)->first();
                        
                        if ($checkpoint->arrange_number == 2 && $task && $task->task_status == 'complete') {
                            continue;
                        }
                        
                        if ($checkpoint->arrange_number == 3 && $task && $task->task_status == 'pending') {
                            continue;
                        }
                    }
                }
                
                // Determine checkpoint status
                if (!$checkpoint->verifies || $checkpoint->verifies->isEmpty()) {
                    $hasNoVerification = true;
                    $allComplete = false;
                } else {
                    $hasIncomplete = false;
                    foreach ($checkpoint->verifies as $verify) {
                        if ($verify->verify_status !== 'complete') {
                            $hasIncomplete = true;
                            $hasIncompleteVerification = true;
                            $allComplete = false;
                            break;
                        }
                    }
                }
            }
            
            // Calculate order status
            if ($hasNoVerification) {
                $orderStatus = 'waiting_for_delivery';
            } else if ($hasIncompleteVerification) {
                $orderStatus = 'in_progress';
            } else if ($allComplete) {
                $orderStatus = 'complete';
            } else {
                $orderStatus = 'waiting_for_delivery';
            }
            
            $orderData['calculated_status'] = $orderStatus;
            
            // Apply status filter if provided (calculated status)
            if ($request->has('status') && !empty($request->status)) {
                if ($orderStatus !== $request->status) {
                    continue;
                }
            }
            
            // Add minimum/maximum order value filters
            if ($request->has('min_total') && !empty($request->min_total)) {
                $orderTotal = array_sum(array_column($orderData['items'], 'total_price'));
                if ($orderTotal < $request->min_total) {
                    continue;
                }
            }
            
            if ($request->has('max_total') && !empty($request->max_total)) {
                $orderTotal = array_sum(array_column($orderData['items'], 'total_price'));
                if ($orderTotal > $request->max_total) {
                    continue;
                }
            }
            
            $allOrdersForLocation[] = $orderData;
        }
        
        // Apply pagination
        $totalOrders = count($allOrdersForLocation);
        $lastPage = max(1, ceil($totalOrders / $perPage));
        if ($currentPage > $lastPage) $currentPage = $lastPage;
        
        $paginatedOrders = array_slice($allOrdersForLocation, ($currentPage - 1) * $perPage, $perPage);
        
        $paginationData = [
            'total' => $totalOrders,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'from' => $totalOrders > 0 ? (($currentPage - 1) * $perPage) + 1 : 0,
            'to' => min($currentPage * $perPage, $totalOrders)
        ];
        
        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $paginatedOrders,
                'pagination' => $paginationData
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error fetching location orders: ' . $e->getMessage()
        ], 500);
    }
}
}
