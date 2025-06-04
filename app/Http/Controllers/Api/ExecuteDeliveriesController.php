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
use DateTime;
use DateTimeZone;

class ExecuteDeliveriesController extends Controller
{
    /**
     * Get deliveries for execution based on user role and filters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
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
                Log::info('ExecuteDeliveriesController: Applying employee filter', [
                    'userID' => $user->userID
                ]);
                $tripsQuery->whereHas('delivery', function($q) use ($user) {
                    $q->where('userID', $user->userID);
                });
            } else if ($user->role === 'admin') {
                Log::info('ExecuteDeliveriesController: Applying admin filter', [
                    'companyID' => $user->companyID
                ]);
                $tripsQuery->whereHas('delivery.user', function($q) use ($user) {
                    $q->where('companyID', $user->companyID);
                });
            }

            // Apply status filter if provided
            if ($request->has('statusFilter') && !empty($request->statusFilter)) {
                $status = $request->statusFilter;
                Log::info('ExecuteDeliveriesController: Applying status filter', [
                    'status' => $status
                ]);

                $tripsQuery->whereHas('delivery', function($q) use ($status) {
                    if ($status === 'pending') {
                        $q->whereNull('start_timestamp')->whereNull('end_timestamp');
                    } else if ($status === 'in_progress') {
                        $q->whereNotNull('start_timestamp')->whereNull('end_timestamp');
                    } else if ($status === 'completed') {
                        $q->whereNotNull('end_timestamp');
                    }
                });
            }

            // Apply date filter if provided
            if ($request->has('dateFilter') && !empty($request->dateFilter)) {
                $date = $request->dateFilter;
                Log::info('ExecuteDeliveriesController: Applying date filter', [
                    'date' => $date
                ]);

                $tripsQuery->whereHas('delivery', function($q) use ($date) {
                    $q->whereDate('scheduled_date', $date);
                });
            }

           

            // Apply search term filter
            if ($request->has('searchTerm') && !empty($request->searchTerm)) {
                $searchTerm = $request->searchTerm;
                Log::info('ExecuteDeliveriesController: Applying search term filter', [
                    'searchTerm' => $searchTerm
                ]);

                $tripsQuery->where(function($query) use ($searchTerm) {
                    // Search by Delivery ID
                    $query->whereHas('delivery', function($q) use ($searchTerm) {
                        $q->where('deliveryID', 'like', '%' . $searchTerm . '%');
                    })
                    // Search by Driver Name
                    ->orWhereHas('delivery.user', function($q) use ($searchTerm) {
                        $q->where('fullname', 'like', '%' . $searchTerm . '%');
                    })
                    // Search by Vehicle Plate
                    ->orWhereHas('delivery.vehicle', function($q) use ($searchTerm) {
                        $q->where('vehicle_plate', 'like', '%' . $searchTerm . '%');
                    });
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

            // If no trips found, return empty data array
            if ($trips->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

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
                    'status' => $delivery->end_timestamp ? 'completed' :
                            ($delivery->start_timestamp ? 'in_progress' : 'pending'),
                    'scheduled_date' => $delivery->scheduled_date,
                    'start_timestamp' => $delivery->start_timestamp,
                    'end_timestamp' => $delivery->end_timestamp,
                    'routes' => []
                ];

                // Collect all unique locations
                $startLocations = [];
                $endLocations = [];
                $locationCheckpoints = [];
                $locationItems = [];

                // First pass: collect all locations and their checkpoints
                foreach ($deliveryTrips as $trip) {
                    if (!$trip->startCheckpoint || !$trip->endCheckpoint) {
                        continue;
                    }

                    $startLocationID = $trip->startCheckpoint->locationID;
                    $endLocationID = $trip->endCheckpoint->locationID;

                    // Add start location if not already added
                    if (!isset($startLocations[$startLocationID])) {
                        $startLocations[$startLocationID] = [
                            'locationID' => $startLocationID,
                            'company_address' => $trip->startCheckpoint->location->company_address
                        ];
                    }

                    // Add end location if not already added
                    if (!isset($endLocations[$endLocationID])) {
                        $endLocations[$endLocationID] = [
                            'locationID' => $endLocationID,
                            'company_address' => $trip->endCheckpoint->location->company_address
                        ];
                    }

                    // Add checkpoint to location
                    if (!isset($locationCheckpoints[$startLocationID])) {
                        $locationCheckpoints[$startLocationID] = [];
                    }
                    if (!isset($locationCheckpoints[$endLocationID])) {
                        $locationCheckpoints[$endLocationID] = [];
                    }

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
                            // Skip if already processed for this checkpoint
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
                                    'orderID' => $orderID
                                ];

                                // Track items for validation later
                                if (!isset($locationItems[$startLocationID])) {
                                    $locationItems[$startLocationID] = [];
                                }
                                if (!isset($locationItems[$startLocationID][$startCheckpoint->checkID])) {
                                    $locationItems[$startLocationID][$startCheckpoint->checkID] = [];
                                }
                                $locationItems[$startLocationID][$startCheckpoint->checkID][$itemID] = true;
                            }
                        }
                    }

                    // Add checkpoint to location if not already added
                    $checkpointExists = false;
                    foreach ($locationCheckpoints[$startLocationID] as $checkpoint) {
                        if ($checkpoint['checkID'] == $startCheckpoint->checkID) {
                            $checkpointExists = true;
                            break;
                        }
                    }

                    if (!$checkpointExists) {
                        $locationCheckpoints[$startLocationID][] = $startCheckpointData;
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
                            // Skip if already processed for this checkpoint
                            if (isset($endCheckpointData['items'][$itemID])) {
                                continue;
                            }

                            // Check if this item exists in ANY start checkpoint for this delivery
                            $itemExistsInAnyStartCheckpoint = false;
                            
                            // Loop through all trips for this delivery to find the item in any start checkpoint
                            foreach ($deliveryTrips as $checkTrip) {
                                if (!$checkTrip->startCheckpoint) {
                                    continue;
                                }
                                
                                $checkStartLocationID = $checkTrip->startCheckpoint->locationID;
                                $checkStartCheckpoint = $checkTrip->startCheckpoint;
                                
                                // Check if the item exists in this start checkpoint
                                if (isset($locationItems[$checkStartLocationID][$checkStartCheckpoint->checkID][$itemID]) ||
                                    (is_array($checkStartCheckpoint->item_record) && in_array($itemID, $checkStartCheckpoint->item_record))) {
                                    $itemExistsInAnyStartCheckpoint = true;
                                    break;
                                }
                            }

                            // Skip items that don't exist in ANY start checkpoint
                            if (!$itemExistsInAnyStartCheckpoint) {
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
                                    'orderID' => $endCheckpoint->orderID
                                ];

                                // Track items for validation later
                                if (!isset($locationItems[$endLocationID])) {
                                    $locationItems[$endLocationID] = [];
                                }
                                if (!isset($locationItems[$endLocationID][$endCheckpoint->checkID])) {
                                    $locationItems[$endLocationID][$endCheckpoint->checkID] = [];
                                }
                                $locationItems[$endLocationID][$endCheckpoint->checkID][$itemID] = true;
                            }
                        }
                    }

                    // Add checkpoint to location if not already added
                    $checkpointExists = false;
                    foreach ($locationCheckpoints[$endLocationID] as $checkpoint) {
                        if ($checkpoint['checkID'] == $endCheckpoint->checkID) {
                            $checkpointExists = true;
                            break;
                        }
                    }

                    if (!$checkpointExists) {
                        $locationCheckpoints[$endLocationID][] = $endCheckpointData;
                    }
                }

                // Identify all connections between start and end locations
                $locationConnections = [];
                foreach ($deliveryTrips as $trip) {
                    if (!$trip->startCheckpoint || !$trip->endCheckpoint) {
                        continue;
                    }

                    $startLocationID = $trip->startCheckpoint->locationID;
                    $endLocationID = $trip->endCheckpoint->locationID;

                    // Track connections
                    if (!isset($locationConnections[$startLocationID])) {
                        $locationConnections[$startLocationID] = [];
                    }
                    if (!in_array($endLocationID, $locationConnections[$startLocationID])) {
                        $locationConnections[$startLocationID][] = $endLocationID;
                    }
                }
                
                // Group locations based on shared connections
                $processedStartLocations = [];
                $processedEndLocations = [];
                $routeGroups = [];
                
                // Process each start location
                foreach ($locationConnections as $startLocationID => $endLocationIDs) {
                    // Skip if this start location is already processed
                    if (in_array($startLocationID, $processedStartLocations)) {
                        continue;
                    }
                    
                    $currentGroup = [
                        'start_locations' => [$startLocationID],
                        'end_locations' => $endLocationIDs
                    ];
                    $processedStartLocations[] = $startLocationID;
                    
                    // Find other start locations that share end locations with this one
                    foreach ($locationConnections as $otherStartID => $otherEndIDs) {
                        // Skip if already processed or same as current
                        if (in_array($otherStartID, $processedStartLocations) || $otherStartID == $startLocationID) {
                            continue;
                        }
                        
                        // Check if there's any overlap in end locations
                        $sharedEndLocations = array_intersect($endLocationIDs, $otherEndIDs);
                        
                        if (!empty($sharedEndLocations)) {
                            // Add this start location to the group
                            $currentGroup['start_locations'][] = $otherStartID;
                            $processedStartLocations[] = $otherStartID;
                            
                            // Add any new end locations to the group
                            $currentGroup['end_locations'] = array_unique(
                                array_merge($currentGroup['end_locations'], $otherEndIDs)
                            );
                        }
                    }
                    
                    // Add the group to our route groups
                    $routeGroups[] = $currentGroup;
                }
                
                // Now build the routes from the groups
                foreach ($routeGroups as $groupIndex => $group) {
                    $routeStartLocations = [];
                    $routeEndLocations = [];
                    
                    // Sort start locations by ID for consistent ordering
                    sort($group['start_locations']);
                    sort($group['end_locations']);
                    
                    // First collect all valid items from end locations in this route
                    $validItemsInRoute = [];
                    foreach ($group['end_locations'] as $endLocationID) {
                        if (isset($endLocations[$endLocationID]) && isset($locationCheckpoints[$endLocationID])) {
                            foreach ($locationCheckpoints[$endLocationID] as $checkpoint) {
                                if (isset($checkpoint['items'])) {
                                    foreach ($checkpoint['items'] as $itemID => $itemData) {
                                        $validItemsInRoute[$itemID] = true;
                                    }
                                }
                            }
                        }
                    }
                    
                    // Build start locations for this route with filtered items
                    foreach ($group['start_locations'] as $startLocationID) {
                        if (isset($startLocations[$startLocationID])) {
                            // Filter checkpoints to only include items that are in end locations
                            $filteredCheckpoints = [];
                            
                            foreach ($locationCheckpoints[$startLocationID] ?? [] as $checkpoint) {
                                $filteredItems = [];
                                
                                foreach ($checkpoint['items'] ?? [] as $itemID => $itemData) {
                                    // Only include items that are in any end location for this route
                                    if (isset($validItemsInRoute[$itemID])) {
                                        $filteredItems[$itemID] = $itemData;
                                    }
                                }
                                
                                // Only include checkpoint if it has items after filtering
                                if (!empty($filteredItems)) {
                                    $filteredCheckpoint = $checkpoint;
                                    $filteredCheckpoint['items'] = $filteredItems;
                                    $filteredCheckpoints[] = $filteredCheckpoint;
                                }
                            }
                            
                            $startLocationWithCheckpoints = [
                                'locationID' => $startLocationID,
                                'company_address' => $startLocations[$startLocationID]['company_address'],
                                'checkpoints' => $filteredCheckpoints
                            ];
                            
                            // Add location status
                            $startLocationWithCheckpoints['location_status'] = $this->getLocationStatus(
                                $deliveryID, 
                                $startLocationWithCheckpoints['checkpoints']
                            );
                            
                            $routeStartLocations[$startLocationID] = $startLocationWithCheckpoints;
                        }
                    }
                    
                    // Build end locations for this route
                    foreach ($group['end_locations'] as $endLocationID) {
                        if (isset($endLocations[$endLocationID])) {
                            $endLocationWithCheckpoints = [
                                'locationID' => $endLocationID,
                                'company_address' => $endLocations[$endLocationID]['company_address'],
                                'checkpoints' => $locationCheckpoints[$endLocationID] ?? []
                            ];
                            
                            // Add location status
                            $endLocationWithCheckpoints['location_status'] = $this->getLocationStatus(
                                $deliveryID, 
                                $endLocationWithCheckpoints['checkpoints']
                            );
                            
                            $routeEndLocations[$endLocationID] = $endLocationWithCheckpoints;
                        }
                    }
                    
                    // Add the route to delivery data
                    if (!empty($routeStartLocations) && !empty($routeEndLocations)) {
                        $deliveryData['routes'][] = [
                            'start_locations' => $routeStartLocations,
                            'end_locations' => $routeEndLocations
                        ];
                    }
                }
                
                // If no routes were created, use the old method as fallback
                if (empty($deliveryData['routes'])) {
                    // First, group routes by end location
                    foreach ($endLocations as $endLocationID => $endLocation) {
                        $connectedStartLocations = [];
                        
                        // Find all start locations that connect to this end location
                        foreach ($startLocations as $startLocationID => $startLocation) {
                            // Check if there's a trip connecting these locations
                            $hasConnection = false;
                            foreach ($deliveryTrips as $trip) {
                                if ($trip->startCheckpoint && $trip->endCheckpoint &&
                                    $trip->startCheckpoint->locationID == $startLocationID &&
                                    $trip->endCheckpoint->locationID == $endLocationID) {
                                    $hasConnection = true;
                                    break;
                                }
                            }
                            
                            if ($hasConnection) {
                                // Add checkpoints to the start location
                                $startLocationWithCheckpoints = [
                                    'locationID' => $startLocationID,
                                    'company_address' => $startLocation['company_address'],
                                    'checkpoints' => $locationCheckpoints[$startLocationID] ?? []
                                ];
                                
                                $connectedStartLocations[$startLocationID] = $startLocationWithCheckpoints;
                            }
                        }
                        
                        // If there are start locations connected to this end location, create a route
                        if (!empty($connectedStartLocations)) {
                            // Create end_locations object with the same structure as start_locations
                            $endLocationsWithStatus = [];
                            $endLocationWithCheckpoints = [
                                'locationID' => $endLocationID,
                                'company_address' => $endLocation['company_address'],
                                'checkpoints' => $locationCheckpoints[$endLocationID] ?? []
                            ];
                            
                            // Add location status
                            foreach ($connectedStartLocations as $startLocationID => &$startLocationData) {
                                $startLocationData['location_status'] = $this->getLocationStatus(
                                    $deliveryID, 
                                    $startLocationData['checkpoints']
                                );
                            }
                            
                            // Add location status for end location
                            $endLocationWithCheckpoints['location_status'] = $this->getLocationStatus(
                                $deliveryID, 
                                $endLocationWithCheckpoints['checkpoints']
                            );
                            
                            $endLocationsWithStatus[$endLocationID] = $endLocationWithCheckpoints;
                            
                            $routeKey = count($deliveryData['routes']);
                            $deliveryData['routes'][$routeKey] = [
                                'start_locations' => $connectedStartLocations,
                                'end_locations' => $endLocationsWithStatus
                            ];
                        }
                    }
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
     * Determine the status of a location based on its checkpoints verification status
     * 
     * @param int $deliveryID
     * @param array $checkpoints
     * @return string
     */
    private function getLocationStatus($deliveryID, $checkpoints)
    {
        $allVerified = true;
        $allComplete = true;
        
        foreach ($checkpoints as $checkpoint) {
            $checkID = $checkpoint['checkID'];
            
            // Check if verification exists for this checkpoint
            $verify = Verify::where('deliveryID', $deliveryID)
                            ->where('checkID', $checkID)
                            ->first();
            
            if (!$verify) {
                // If any checkpoint has no verification, status is pending
                return 'pending';
            }
            
            // If verification status is not complete, mark as not complete
            if ($verify->verify_status !== 'complete') {
                $allComplete = false;
            }
        }
        
        // If we got here, all checkpoints have verifications
        // Return status based on whether all are complete
        return $allComplete ? 'complete' : 'in_progress';
    }
    /**
     * Determine the delivery status based on timestamps
     *
     * @param Delivery $delivery
     * @return string
     */
    private function determineDeliveryStatus($delivery)
    {
        if ($delivery->end_timestamp) {
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
        
        $delivery = Delivery::with(['user', 'vehicle', 'trips.startCheckpoint.location', 'trips.endCheckpoint.location'])
            ->findOrFail($deliveryID);
            
        // Format the delivery data as needed
        // This will depend on your specific data structure
        
        return $delivery;
    }
}
