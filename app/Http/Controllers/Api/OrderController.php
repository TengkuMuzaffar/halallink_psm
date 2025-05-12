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
                                'location_name' => $item->location ? $item->location->company_address : 'Unknown',
                                'location_status' => 'pending',
                                'checkpoints' => [],
                                'items' => []
                            ];
                        }
                        
                        // Add item to the location's items list
                        $locationMap[$locationID]['items'][$item->itemID] = [
                            'itemID' => $item->itemID,
                            'item_name' => $item->poultry ? $item->poultry->poultry_name : 'Unknown',
                            'quantity' => $cartItem->quantity,
                            'price_at_purchase' => $cartItem->price_at_purchase,
                            'item_info' => $item
                        ];
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
                                'timestamp' => $checkpoint->timestamp,
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
                    $orderStatus = 'waiting_delivery';
                    
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
                            $orderStatus = 'waiting_delivery';
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
    public function getStats()
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
                $orderIDs = DB::table('carts')
                    ->join('items', 'carts.itemID', '=', 'items.itemID')
                    ->join('users', 'items.userID', '=', 'users.userID')
                    ->where('users.companyID', $companyID)
                    ->distinct()
                    ->pluck('carts.orderID');
                
                // Get all orders with their checkpoints and verifies
                $orders = Order::whereIn('orderID', $orderIDs)
                    ->with(['items.location', 'checkpoints.verifies'])
                    ->get();
                
                // Calculate stats based on the custom status logic
                $pendingCount = 0;
                $completedCount = 0;
                $processingCount = 0;
                $waitingCount = 0;
                
                foreach ($orders as $order) {
                    // Group items by locationID
                    $itemsByLocation = [];
                    foreach ($order->items as $item) {
                        $locationID = $item->locationID;
                        if (!isset($itemsByLocation[$locationID])) {
                            $itemsByLocation[$locationID] = [
                                'location' => $item->location,
                                'items' => [],
                                'status' => 'waiting_delivery' // Default status
                            ];
                        }
                        $itemsByLocation[$locationID]['items'][] = $item;
                    }
                    
                    // Process each location's status
                    $allLocationsComplete = true;
                    $hasProcessing = false;
                    $hasWaiting = false;
                    
                    foreach ($itemsByLocation as $locationID => &$locationData) {
                        // Get checkpoints for this order and location
                        $checkpoints = $order->checkpoints->where('locationID', $locationID);
                        
                        if ($checkpoints->isEmpty()) {
                            $locationData['status'] = 'waiting_delivery';
                            $allLocationsComplete = false;
                            $hasWaiting = true;
                            continue;
                        }
                        
                        // Check if all checkpoints have verifies
                        $allCheckpointsHaveVerifies = true;
                        $allVerifiesComplete = true;
                        
                        foreach ($checkpoints as $checkpoint) {
                            if ($checkpoint->verifies->isEmpty()) {
                                $allCheckpointsHaveVerifies = false;
                                break;
                            }
                            
                            // Check if any verify is not complete
                            foreach ($checkpoint->verifies as $verify) {
                                if ($verify->verify_status !== 'complete') {
                                    $allVerifiesComplete = false;
                                    break;
                                }
                            }
                        }
                        
                        if (!$allCheckpointsHaveVerifies) {
                            $locationData['status'] = 'waiting_delivery';
                            $allLocationsComplete = false;
                            $hasWaiting = true;
                        } else if (!$allVerifiesComplete) {
                            $locationData['status'] = 'processing';
                            $allLocationsComplete = false;
                            $hasProcessing = true;
                        } else {
                            $locationData['status'] = 'complete';
                        }
                    }
                    
                    // Increment the appropriate counter
                    if ($allLocationsComplete) {
                        $completedCount++;
                    } else {
                        $pendingCount++;
                        if ($hasProcessing) {
                            $processingCount++;
                        }
                        if ($hasWaiting) {
                            $waitingCount++;
                        }
                    }
                }
                
                return response()->json([
                    'total_orders' => $orders->count(),
                    'pending_orders' => $pendingCount,
                    'completed_orders' => $completedCount,
                    'processing_orders' => $processingCount,
                    'waiting_orders' => $waitingCount,
                    'success' => true
                ]);
                
            } else {
                // For SME and other company types: Use the order_status field directly
                $totalOrders = Order::where('companyID', $companyID)->count();
                $pendingOrders = Order::where('companyID', $companyID)
                    ->whereIn('order_status', ['pending', 'processing', 'waiting_delivery'])
                    ->count();
                $completedOrders = Order::where('companyID', $companyID)
                    ->where('order_status', 'complete')
                    ->count();
                
                return response()->json([
                    'total_orders' => $totalOrders,
                    'pending_orders' => $pendingOrders,
                    'completed_orders' => $completedOrders,
                    'success' => true
                ]);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve order statistics: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}