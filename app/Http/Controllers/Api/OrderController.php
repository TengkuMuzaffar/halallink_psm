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
            
            // Get pagination parameters
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            
            // Get locations belonging to the company
            $locationsQuery = Location::where('companyID', $companyID);
            
            // Apply search filter if provided (search by company_name)
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = '%' . $request->search . '%';
                $locationsQuery->where(function($q) use ($searchTerm) {
                    $q->where('company_address', 'LIKE', $searchTerm)
                      ->orWhere('company_name', 'LIKE', $searchTerm);
                });
            }
            
            $locations = $locationsQuery->get();
            $locationIDs = $locations->pluck('locationID')->toArray();
            
            // Initialize array for organizing data
            $groupedData = [];
            
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
                foreach ($orderCheckpoints as $orderID => $checkpointList) {
                    $order = Order::with(['user'])->find($orderID);
                    if (!$order) continue;
                    
                    // Initialize order data
                    $orderData = [
                        'orderID' => $orderID,
                        'order_status' => $order->order_status,
                        'created_at' => $order->created_at,
                        'user' => $order->user,
                        'checkpoints' => []
                    ];
                    
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
                        
                        // Get items from checkpoint's item_record
                        $checkpointItems = [];
                        if ($checkpoint->item_record) {
                            $itemIDs = is_array($checkpoint->item_record) 
                                ? $checkpoint->item_record 
                                : json_decode($checkpoint->item_record, true);
                            
                            if (is_array($itemIDs) && isset($cartItemsByOrder[$orderID])) {
                                // Filter cart items for this checkpoint
                                $relevantCartItems = array_filter($cartItemsByOrder[$orderID], function($cartItem) use ($itemIDs) {
                                    return in_array($cartItem->itemID, $itemIDs);
                                });
                                
                                foreach ($relevantCartItems as $cartItem) {
                                    $item = $cartItem->item;
                                    
                                    if ($item) {
                                        $checkpointItems[] = [
                                            'itemID' => $item->itemID,
                                            'cartID' => $cartItem->cartID,
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
                                    }
                                }
                            }
                        }
                        
                        // Add checkpoint data
                        $orderData['checkpoints'][] = [
                            'checkID' => $checkpoint->checkID,
                            'checkpoint_status' => $checkpointStatus,
                            'arrange_number' => $checkpoint->arrange_number,
                            'items' => $checkpointItems
                        ];
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
                    
                    // Add order data to location
                    $groupedData[$locationID]['orders'][$orderID] = $orderData;
                }
                
                // Convert orders from associative to indexed array
                $groupedData[$locationID]['orders'] = array_values($groupedData[$locationID]['orders']);
                
                // Remove locations with no orders after filtering
                if (empty($groupedData[$locationID]['orders'])) {
                    unset($groupedData[$locationID]);
                }
            }
            
            // Convert to array and paginate manually
            $locationArray = array_values($groupedData);
            $total = count($locationArray);
            
            $paginatedData = array_slice($locationArray, ($page - 1) * $perPage, $perPage);
            
            $pagination = [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'from' => (($page - 1) * $perPage) + 1,
                'to' => min($page * $perPage, $total)
            ];
            
            return response()->json([
                'success' => true,
                'data' => $paginatedData,
                'pagination' => $pagination
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
                $query = Order::with(['user', 'items.poultry', 'items.location', 'payment', 'checkpoints.verifies'])
                    ->whereIn('orderID', function($subQuery) use ($companyID) {
                        $subQuery->select('carts.orderID')
                            ->from('carts')
                            ->join('items', 'carts.itemID', '=', 'items.itemID')
                            ->join('users', 'items.userID', '=', 'users.userID')
                            ->where('users.companyID', $companyID)
                            ->distinct(); // Ensure unique orderIDs
                    });
            } else {
                // For SME and other company types: Direct query on orders
                $query = Order::where('companyID', $companyID)
                    ->with(['user', 'items.poultry', 'items.location', 'payment', 'checkpoints.verifies']);
            }
            
            // Apply search if provided
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('orderID', 'LIKE', $searchTerm)
                      ->orWhere('order_status', 'LIKE', $searchTerm)
                      ->orWhere('total_amount', 'LIKE', $searchTerm);
                });
            }
            
            // Apply status filter if provided
            if ($request->has('status') && !empty($request->status)) {
                $query->where('order_status', $request->status);
            }
            
            // Apply date range filters if provided
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);
            
            // Paginate the results
            $perPage = $request->input('per_page', 10);
            $orders = $query->paginate($perPage);
            
            // Restructure the response data
            $restructuredData = [];
            
            foreach ($orders as $order) {
                $orderData = [
                    'orderID' => $order->orderID,
                    'order_status' => $order->order_status,
                    'total_amount' => $order->total_amount,
                    'delivery_address' => $order->location->company_address,
                    'created_at' => $order->created_at,
                    'user' => $order->user,
                    'payment' => $order->payment,
                    'locations' => []
                ];
                
                // Get all cart items for this order
                $cartItems = Cart::where('orderID', $order->orderID)->get();
                
                // Create a map of locationIDs from items in the cart
                $locationMap = [];
                
                foreach ($cartItems as $cartItem) {
                    $item = Item::with('location', 'poultry')->find($cartItem->itemID);
                    
                    if ($item && $item->locationID) {
                        $locationID = $item->locationID;
                        
                        if (!isset($locationMap[$locationID])) {
                            $locationMap[$locationID] = [
                                'locationID' => $locationID,
                                'checkpoints' => []
                            ];
                        }
                    }
                }
                
                // Now get checkpoints that match both orderID and the locationIDs we found
                if (count($locationMap) > 0) {
                    $checkpoints = Checkpoint::with('verifies')
                        ->where('orderID', $order->orderID)
                        ->whereIn('locationID', array_keys($locationMap))
                        ->get();
                    
                    foreach ($checkpoints as $checkpoint) {
                        $locationID = $checkpoint->locationID;
                        
                        if (isset($locationMap[$locationID])) {
                            // Determine checkpoint status based on verifies
                            $checkpointStatus = 'pending';
                            
                            if (!$checkpoint->verifies || count($checkpoint->verifies) === 0) {
                                $checkpointStatus = 'pending';
                            } else {
                                $hasIncomplete = false;
                                foreach ($checkpoint->verifies as $verify) {
                                    if ($verify->verify_status !== 'complete') {
                                        $hasIncomplete = true;
                                        break;
                                    }
                                }
                                
                                if ($hasIncomplete) {
                                    $checkpointStatus = 'processing';
                                } else {
                                    $checkpointStatus = 'complete';
                                }
                            }
                            
                            // Get items from checkpoint's item_record that match our cart items
                            $checkpointItems = [];
                            if ($checkpoint->item_record) {
                                $itemIDs = is_array($checkpoint->item_record) 
                                    ? $checkpoint->item_record 
                                    : json_decode($checkpoint->item_record, true);
                                
                                if (is_array($itemIDs)) {
                                    foreach ($itemIDs as $itemID) {
                                        // Only include items that are in this location's items list
                                        if (isset($locationMap[$locationID]['items'][$itemID])) {
                                            $checkpointItems[$itemID] = $locationMap[$locationID]['items'][$itemID];
                                        }
                                    }
                                }
                            }
                            
                            $locationMap[$locationID]['checkpoints'][] = [
                                'checkID' => $checkpoint->checkID,
                                'checkpoint_status' => $checkpointStatus,
                                'end_timestamp' => $checkpoint->end_timestamp,
                                'notes' => $checkpoint->notes,
                                'items' => $checkpointItems
                            ];
                            
                            // Update location status based on checkpoint status
                            if ($checkpointStatus === 'processing' && $locationMap[$locationID]['location_status'] !== 'complete') {
                                $locationMap[$locationID]['location_status'] = 'processing';
                            } else if ($checkpointStatus === 'complete') {
                                $locationMap[$locationID]['location_status'] = 'complete';
                            }
                        }
                    }
                }
                
                // Calculate overall order status for broiler companies
                if ($companyType === 'broiler') {
                    $orderStatus = 'waiting_for_delivery';
                    
                    if (count($locationMap) > 0) {
                        $allLocationsComplete = true;
                        $allLocationsHaveVerifies = true;
                        
                        foreach ($locationMap as $location) {
                            if (empty($location['checkpoints'])) {
                                $allLocationsHaveVerifies = false;
                                $allLocationsComplete = false;
                                continue;
                            }
                            
                            $locationHasVerifies = true;
                            $locationIsComplete = true;
                            
                            foreach ($location['checkpoints'] as $checkpoint) {
                                if ($checkpoint['checkpoint_status'] === 'pending') {
                                    $locationHasVerifies = false;
                                    $locationIsComplete = false;
                                    break;
                                } else if ($checkpoint['checkpoint_status'] === 'processing') {
                                    $locationIsComplete = false;
                                }
                            }
                            
                            if (!$locationHasVerifies) {
                                $allLocationsHaveVerifies = false;
                            }
                            
                            if (!$locationIsComplete) {
                                $allLocationsComplete = false;
                            }
                        }
                        
                        if (!$allLocationsHaveVerifies) {
                            $orderStatus = 'waiting_for_delivery';
                        } else if (!$allLocationsComplete) {
                            $orderStatus = 'processing';
                        } else {
                            $orderStatus = 'complete';
                        }
                    }
                    
                    $orderData['calculated_status'] = $orderStatus;
                }
                
                $orderData['locations'] = array_values($locationMap);
                $restructuredData[$order->orderID] = $orderData;
            }
            
            return response()->json([
                'data' => array_values($restructuredData),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching orders: ' . $e->getMessage()], 500);
        }
    }
}