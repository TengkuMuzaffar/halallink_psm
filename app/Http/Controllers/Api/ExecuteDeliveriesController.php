<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Delivery;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Models\Location;
use App\Models\Checkpoint;
use App\Models\Verify;
use App\Models\Cart;
use App\Models\Item;
use App\Models\Order;

class ExecuteDeliveriesController extends Controller
{
    public function index(Request $request)
    {
        try {
            Log::info('ExecuteDeliveriesController: Starting index method');
            
            // Get the authenticated user
            $user = Auth::user();
            Log::info('ExecuteDeliveriesController: Authenticated user', [
                'userID' => $user->userID,
                'role' => $user->role,
                'companyID' => $user->companyID ?? 'null'
            ]);
            
            // Start with a base query to get all trips with non-null deliveryID
            Log::info('ExecuteDeliveriesController: Building base query for trips');
            $tripsQuery = Trip::whereNotNull('deliveryID');
            
            // Apply role-based filtering
            if ($user->role === 'employee') {
                // If user is an employee, only show trips for deliveries assigned to them
                Log::info('ExecuteDeliveriesController: Applying employee filter', [
                    'userID' => $user->userID
                ]);
                $tripsQuery->whereHas('delivery', function($q) use ($user) {
                    $q->where('userID', $user->userID);
                });
            } else if ($user->role === 'admin') {
                // If user is an admin, show all trips for deliveries from their company
                Log::info('ExecuteDeliveriesController: Applying admin filter', [
                    'companyID' => $user->companyID
                ]);
                $tripsQuery->whereHas('delivery.user', function($q) use ($user) {
                    $q->where('companyID', $user->companyID);
                });
            }
            
            // Apply status filter if provided
            if ($request->has('status') && !empty($request->status)) {
                $status = $request->status;
                Log::info('ExecuteDeliveriesController: Applying status filter', [
                    'status' => $status
                ]);
                
                $tripsQuery->whereHas('delivery', function($q) use ($status) {
                    if ($status === 'pending') {
                        $q->whereNull('start_timestamp')->whereNull('arrive_timestamp');
                    } else if ($status === 'in_progress') {
                        $q->whereNotNull('start_timestamp')->whereNull('arrive_timestamp');
                    } else if ($status === 'completed') {
                        $q->whereNotNull('arrive_timestamp');
                    }
                });
            }
            
            // Eager load relationships
            $tripsQuery->with([
                'delivery.user', 
                'delivery.vehicle',
                'startCheckpoint.location',
                'endCheckpoint.location',
                'order.carts.item.poultry'
            ]);
            
            // Execute the query
            $trips = $tripsQuery->get();
            Log::info('ExecuteDeliveriesController: Found ' . $trips->count() . ' trips');
            
            // Group trips by deliveryID
            $deliveryGroups = $trips->groupBy('deliveryID');
            
            $result = [];
            
            foreach ($deliveryGroups as $deliveryID => $deliveryTrips) {
                Log::info('ExecuteDeliveriesController: Processing delivery', [
                    'deliveryID' => $deliveryID,
                    'trip_count' => $deliveryTrips->count()
                ]);
                
                // Get the delivery details
                $delivery = $deliveryTrips->first()->delivery;
                
                // Initialize the delivery data structure
                $deliveryData = [
                    'deliveryID' => $deliveryID,
                    'driver' => [
                        'userID' => $delivery->user->userID ?? null,
                        'fullname' => $delivery->user->fullname ?? 'Unknown'
                    ],
                    'vehicle' => [
                        'vehicleID' => $delivery->vehicle->vehicleID ?? null,
                        'vehicle_plate' => $delivery->vehicle->vehicle_plate ?? 'Unknown'
                    ],
                    'status' => $delivery->arrive_timestamp ? 'completed' : 
                               ($delivery->start_timestamp ? 'in_progress' : 'pending'),
                    'scheduled_date' => $delivery->scheduled_date,
                    'start_timestamp' => $delivery->start_timestamp,
                    'arrive_timestamp' => $delivery->arrive_timestamp,
                    'routes' => []
                ];
                
                // Group trips by routes (unique combinations of start and end locations)
                $routeGroups = [];
                
                // First, identify all unique routes
                foreach ($deliveryTrips as $trip) {
                    if (!$trip->startCheckpoint || !$trip->endCheckpoint) {
                        continue;
                    }
                    
                    $startLocationID = $trip->startCheckpoint->locationID;
                    $endLocationID = $trip->endCheckpoint->locationID;
                    
                    // Create a unique key for this route
                    $routeKey = $startLocationID . '-' . $endLocationID;
                    
                    if (!isset($routeGroups[$routeKey])) {
                        $routeGroups[$routeKey] = [
                            'start_location' => [
                                'locationID' => $startLocationID,
                                'company_address' => $trip->startCheckpoint->location->company_address,
                                'checkpoints' => []
                            ],
                            'end_location' => [
                                'locationID' => $endLocationID,
                                'company_address' => $trip->endCheckpoint->location->company_address,
                                'checkpoints' => []
                            ]
                        ];
                    }
                }
                
                // Now populate each route with its checkpoints and items
                foreach ($deliveryTrips as $trip) {
                    if (!$trip->startCheckpoint || !$trip->endCheckpoint) {
                        continue;
                    }
                    
                    $startLocationID = $trip->startCheckpoint->locationID;
                    $endLocationID = $trip->endCheckpoint->locationID;
                    $routeKey = $startLocationID . '-' . $endLocationID;
                    
                    // Process start checkpoint
                    $startCheckpoint = $trip->startCheckpoint;
                    $startCheckpointData = [
                        'checkID' => $startCheckpoint->checkID,
                        'items' => []
                    ];
                    
                    // Get orderID from trip or checkpoint
                    $orderID = $trip->orderID ?? $startCheckpoint->orderID;
                    
                    // Process items in the checkpoint
                    if ($startCheckpoint->item_record) {
                        foreach ($startCheckpoint->item_record as $itemID) {
                            // Skip if already processed
                            if (isset($startCheckpointData['items'][$itemID])) {
                                continue;
                            }
                            
                            // Get quantity from cart
                            $quantity = 0;
                            if ($orderID) {
                                $cart = Cart::where('orderID', $orderID)
                                    ->where('itemID', $itemID)
                                    ->first();
                                if ($cart) {
                                    $quantity = $cart->quantity;
                                }
                            }
                            
                            // Get item details
                            $item = Item::with('poultry')->find($itemID);
                            if ($item) {
                                $startCheckpointData['items'][$itemID] = [
                                    'item_name' => $item->poultry ? $item->poultry->poultry_name : 'Unknown',
                                    'quantity' => $quantity,
                                    'orderID' => $orderID // Add orderID to start location items
                                ];
                            }
                        }
                    }
                    
                    // Add start checkpoint to route if not already added
                    $startCheckpointExists = false;
                    foreach ($routeGroups[$routeKey]['start_location']['checkpoints'] as $checkpoint) {
                        if ($checkpoint['checkID'] == $startCheckpoint->checkID) {
                            $startCheckpointExists = true;
                            break;
                        }
                    }
                    
                    if (!$startCheckpointExists) {
                        $routeGroups[$routeKey]['start_location']['checkpoints'][] = $startCheckpointData;
                    }
                    
                    // Process end checkpoint
                    $endCheckpoint = $trip->endCheckpoint;
                    $endCheckpointData = [
                        'checkID' => $endCheckpoint->checkID,
                        'items' => []
                    ];
                    
                    // Process items in the end checkpoint
                    if ($endCheckpoint->item_record) {
                        foreach ($endCheckpoint->item_record as $itemID) {
                            // Skip if already processed
                            if (isset($endCheckpointData['items'][$itemID])) {
                                continue;
                            }
                            
                            // Get quantity from cart
                            $quantity = 0;
                            if ($orderID) {
                                $cart = Cart::where('orderID', $orderID)
                                    ->where('itemID', $itemID)
                                    ->first();
                                if ($cart) {
                                    $quantity = $cart->quantity;
                                }
                            }
                            
                            // Get item details
                            $item = Item::with('poultry')->find($itemID);
                            if ($item) {
                                $endCheckpointData['items'][$itemID] = [
                                    'item_name' => $item->poultry ? $item->poultry->poultry_name : 'Unknown',
                                    'quantity' => $quantity,
                                    'orderID' => $endCheckpoint->orderID // Add orderID from checkpoint
                                ];
                            }
                        }
                    }
                    
                    // Add end checkpoint to route if not already added
                    $endCheckpointExists = false;
                    foreach ($routeGroups[$routeKey]['end_location']['checkpoints'] as $checkpoint) {
                        if ($checkpoint['checkID'] == $endCheckpoint->checkID) {
                            $endCheckpointExists = true;
                            break;
                        }
                    }
                    
                    if (!$endCheckpointExists) {
                        $routeGroups[$routeKey]['end_location']['checkpoints'][] = $endCheckpointData;
                    }
                }
                
                // Add routes to delivery data
                $routeIndex = 1;
                foreach ($routeGroups as $routeData) {
                    $deliveryData['routes'][$routeIndex] = $routeData;
                    $routeIndex++;
                }
                
                $result[$deliveryID] = $deliveryData;
            }
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            Log::error('ExecuteDeliveriesController: Error in index method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve deliveries: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Determine the delivery status based on timestamps
     *
     * @param Delivery $delivery
     * @return string
     */
    private function determineDeliveryStatus($delivery)
    {
        if ($delivery->arrive_timestamp) {
            return 'completed';
        } else if ($delivery->start_timestamp) {
            return 'in_progress';
        } else {
            return 'pending';
        }
    }
    
    /**
     * Start a delivery by updating the start_timestamp
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $deliveryID
     * @return \Illuminate\Http\Response
     */
    public function startDelivery(Request $request, $deliveryID)
    {
        try {
            // Find the delivery
            $delivery = Delivery::findOrFail($deliveryID);
            
            // Check if delivery is already started
            if ($delivery->start_timestamp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery has already been started'
                ], 400);
            }
            
            // Update the start_timestamp
            $delivery->start_timestamp = now();
            $delivery->save();
            
            // Get the updated delivery with all related data
            $updatedDelivery = $this->getDeliveryWithDetails($deliveryID);
            
            return response()->json([
                'success' => true,
                'message' => 'Delivery started successfully',
                'data' => $updatedDelivery
            ]);
        } catch (\Exception $e) {
            Log::error('Error starting delivery: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to start delivery: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get delivery with all related details
     * 
     * @param int $deliveryID
     * @return array
     */
    private function getDeliveryWithDetails($deliveryID)
    {
        // This method should return the delivery with all related data
        // Similar to what's returned in the index method
        // You can extract this logic from your index method to avoid duplication
        
        $delivery = Delivery::with(['user', 'vehicle', 'trips.startLocation', 'trips.endLocation'])
            ->findOrFail($deliveryID);
            
        // Format the delivery data as needed
        // This will depend on your specific data structure
        
        return $delivery;
    }
}