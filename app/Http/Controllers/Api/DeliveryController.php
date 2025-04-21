<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Checkpoint;
use App\Models\Location;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    /**
     * Get all pending deliveries grouped by location
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            // Start with orders that are not complete
            $query = Order::where('order_status', '!=', 'complete');
            
            // Get pagination parameters
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            
            // Filter by locationID if provided
            $locationID = $request->input('locationID');
            
            // Get orders with their cart items and related data
            $paginatedOrders = $query->with([
                'cartItems.item.slaughterhouse',
                'checkpoints' => function($query) {
                    $query->where('arrange_number', 2);
                }
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
            
            // Initialize the result array
            $groupedData = [];
            
            // Process each order
            foreach ($paginatedOrders as $order) {
                // Skip orders without cart items
                if ($order->cartItems->isEmpty()) {
                    continue;
                }
                
                // Process each cart item
                foreach ($order->cartItems as $cartItem) {
                    if (!$cartItem->item) {
                        continue; // Skip if item doesn't exist
                    }
                    
                    $item = $cartItem->item;
                    
                    // Get the checkpoint with arrange_number = 2 for this order and item
                    $checkpoint = $order->checkpoints
                        ->where('arrange_number', 2)
                        ->where('itemID', $item->itemID)
                        ->first();
                    
                    // If no checkpoint found or no slaughterhouse location, skip
                    if (!$checkpoint && !$item->slaughterhouse_locationID) {
                        continue;
                    }
                    
                    // Determine which locationID to use
                    $targetLocationID = $checkpoint ? $checkpoint->locationID : $item->slaughterhouse_locationID;
                    
                    // If filtering by locationID and it doesn't match, skip
                    if ($locationID && $targetLocationID != $locationID) {
                        continue;
                    }
                    
                    // Get location details
                    $location = Location::find($targetLocationID);
                    if (!$location) {
                        continue; // Skip if location not found
                    }
                    
                    // Prepare item data
                    $itemData = [
                        'itemID' => $item->itemID,
                        'item_name' => $item->poultry ? $item->poultry->poultry_name : 'Unknown',
                        'measurement_type' => $item->measurement_type,
                        'measurement_value' => $item->measurement_value,
                        'price' => $item->price,
                        'quantity' => $cartItem->quantity,
                        'total_price' => $cartItem->price_at_purchase * $cartItem->quantity,
                        'checkID' => $checkpoint ? $checkpoint->checkID : null,
                    ];
                    
                    // Initialize location in groupedData if not exists
                    if (!isset($groupedData[$targetLocationID])) {
                        $groupedData[$targetLocationID] = [
                            'company_address' => $location->company_address,
                            'orders' => []
                        ];
                    }
                    
                    // Initialize order in location if not exists
                    if (!isset($groupedData[$targetLocationID]['orders'][$order->orderID])) {
                        $groupedData[$targetLocationID]['orders'][$order->orderID] = [];
                    }
                    
                    // Add item to the order
                    $groupedData[$targetLocationID]['orders'][$order->orderID][] = $itemData;
                }
            }
            
            // Format the response to match what the frontend expects
            return response()->json([
                'success' => true,
                'data' => $groupedData,
                'pagination' => [
                    'current_page' => $paginatedOrders->currentPage(),
                    'last_page' => $paginatedOrders->lastPage(),
                    'per_page' => $paginatedOrders->perPage(),
                    'total' => $paginatedOrders->total(),
                    'from' => $paginatedOrders->firstItem(),
                    'to' => $paginatedOrders->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching delivery data: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch delivery data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get delivery details for a specific location
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $locationID
     * @return \Illuminate\Http\Response
     */
    public function getByLocation(Request $request, $locationID)
    {
        try {
            // Validate locationID
            $location = Location::find($locationID);
            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location not found'
                ], 404);
            }
            
            // Set locationID in request and call index method
            $request->merge(['locationID' => $locationID]);
            return $this->index($request);
        } catch (\Exception $e) {
            Log::error('Error fetching delivery data by location: ' . $e->getMessage(), [
                'locationID' => $locationID,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch delivery data by location',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Assign delivery to checkpoints
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function assignDelivery(Request $request)
    {
        try {
            // Validate request
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'checkIDs' => 'required|array',
                'checkIDs.*' => 'required|exists:checkpoints,checkID',
                'vehicleID' => 'required|exists:vehicles,vehicleID',
                'userID' => 'required|exists:users,userID',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Start a database transaction
            DB::beginTransaction();
            
            try {
                // Create a verify record
                $verify = new \App\Models\Verify();
                $verify->userID = $request->userID;
                $verify->vehicleID = $request->vehicleID;
                $verify->verify_status = 'pending';
                $verify->save();
                
                // Create a delivery record
                $delivery = new \App\Models\Delivery();
                $delivery->verifyID = $verify->verifyID;
                $delivery->start_timestamp = now();
                $delivery->save();
                
                // Update checkpoints with deliveryID
                Checkpoint::whereIn('checkID', $request->checkIDs)
                    ->update(['deliveryID' => $delivery->deliveryID]);
                
                // Commit the transaction
                DB::commit();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Delivery assigned successfully',
                    'data' => [
                        'deliveryID' => $delivery->deliveryID,
                        'verifyID' => $verify->verifyID
                    ]
                ]);
            } catch (\Exception $e) {
                // Rollback the transaction if something goes wrong
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error assigning delivery: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign delivery',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}