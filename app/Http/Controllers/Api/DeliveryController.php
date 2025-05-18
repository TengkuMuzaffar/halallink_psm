<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Checkpoint;
use App\Models\Location;
use App\Models\Item;
use App\Models\User;
use App\Models\Delivery;
use App\Models\Verify;
use App\Models\Task;
use App\Models\Vehicle;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            // Add request logging
            Log::info('Delivery index request received', [
                'locationID' => $request->input('locationID'),
                'per_page' => $request->input('per_page', 10),
                'page' => $request->input('page', 1)
            ]);

            // Get pagination parameters
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            
            // Filter by locationID if provided
            $locationIDFilter = $request->input('locationID');
            
            // Start with orders that are not complete
            $incompleteOrders = Order::where('order_status', '!=', 'complete')
                ->orderBy('created_at', 'asc') // First in, first out
                ->get();
                
            // Initialize arrays for organizing trips
            $phase1Array = [];
            $phase2Array = [];
            $displayArray = [];
            
            // Process each incomplete order
            foreach ($incompleteOrders as $order) {
                $orderID = $order->orderID;
                
                // Get trips for this order that don't have a deliveryID assigned yet
                $trips = Trip::where('orderID', $orderID)
                    ->whereNull('deliveryID') // Only get trips without a deliveryID
                    ->with(['startCheckpoint', 'endCheckpoint'])
                    ->get();
                
                foreach ($trips as $trip) {
                    // Check if end checkpoint has arrange_number 2
                    $endCheckpoint = $trip->endCheckpoint;

                    if ($endCheckpoint && $endCheckpoint->arrange_number == 2) {
                        // This is a phase 1 trip (to slaughterhouse)
                        $phase1Array[] = $trip;
                        
                        // Check if there's a task for this checkpoint
                        $task = Task::where('checkID', $endCheckpoint->checkID)->first();
                        
                        // Tasks are always created, so we're just checking status
                        if ($task && $task->task_status == 'complete') {
                            // Task is complete, add to display
                            $displayArray[] = [
                                'trip' => $trip,
                                'phase' => 1,
                                'status' => 'task_complete',
                                'items' => []
                            ];
                        } else {
                            // Task exists but is still in progress
                            $displayArray[] = [
                                'trip' => $trip,
                                'phase' => 1,
                                'status' => 'task_in_progress',
                                'items' => []
                            ];
                        }
                    } else {
                        // This is a phase 2 trip (from slaughterhouse to destination)
                        $phase2Array[] = $trip;
                        
                        // For phase 2, we always display
                        $displayArray[] = [
                            'trip' => $trip,
                            'phase' => 2,
                            'status' => 'ready',
                            'items' => []
                        ];
                    }
                }
            }
            
            // Now process items for each trip in the display array using end checkpoint's item_record
            foreach ($displayArray as &$displayItem) {
                $trip = $displayItem['trip'];
                $endCheckpoint = $trip->endCheckpoint;
                
                if (!$endCheckpoint || !$endCheckpoint->item_record) continue;
                
                // Get items from the end checkpoint's item_record
                $items = Item::whereIn('itemID', $endCheckpoint->item_record)
                    ->with(['poultry', 'location', 'slaughterhouse'])
                    ->get();
                
                foreach ($items as $item) {
                    // Get cart item for quantity and price information
                    $cartItem = Cart::where('orderID', $trip->orderID)
                        ->where('itemID', $item->itemID)
                        ->first();
                        
                    if (!$cartItem) continue;
                    
                    $displayItem['items'][] = [
                        'itemID' => $item->itemID,
                        'item_name' => $item->poultry ? $item->poultry->poultry_name : 'Unknown',
                        'measurement_type' => $item->measurement_type,
                        'measurement_value' => $item->measurement_value,
                        'price' => $item->price,
                        'quantity' => $cartItem->quantity,
                        'total_price' => $cartItem->price_at_purchase * $cartItem->quantity,
                        'supplier_locationID' => $item->locationID,
                        'supplier_location_address' => $item->location ? $item->location->company_address : 'Unknown',
                        'slaughterhouse_locationID' => $item->slaughterhouse_locationID,
                        'slaughterhouse_location_address' => $item->slaughterhouse ? $item->slaughterhouse->company_address : 'N/A'
                    ];
                }
            }
            
            // Apply location filter if provided
            if ($locationIDFilter) {
                $displayArray = array_filter($displayArray, function($item) use ($locationIDFilter) {
                    $trip = $item['trip'];
                    $startCheckpoint = $trip->startCheckpoint;
                    $endCheckpoint = $trip->endCheckpoint;
                    
                    return ($startCheckpoint && $startCheckpoint->locationID == $locationIDFilter) ||
                           ($endCheckpoint && $endCheckpoint->locationID == $locationIDFilter);
                });
            }
            
            // Group by location for frontend display
            $groupedData = [];
            
            foreach ($displayArray as $item) {
                $trip = $item['trip'];
                $endCheckpoint = $trip->endCheckpoint;
                $startCheckpoint = $trip->startCheckpoint;
                
                if (!$endCheckpoint || !$startCheckpoint) continue;
                // Skip if not a slaughterhouse location
                // Determine which checkpoint to use based on task status
                $checkpointToUse = null;
                $locationID = null;
                
                // Get the task for the end checkpoint
                $task = Task::where('checkID', $endCheckpoint->checkID)->first();
                
                if ($task) {
                    if ($task->task_status !== 'complete' && $task->start_timestamp === null) {
                        // If task is not complete and hasn't started, use end checkpoint
                        $checkpointToUse = $endCheckpoint;
                    } else if ($task->task_status === 'complete' && 
                              $task->start_timestamp !== null && 
                              $task->finish_timestamp !== null) {
                        // If task is complete with timestamps, use start checkpoint
                        $checkpointToUse = $startCheckpoint;
                    } else {
                        // Default to end checkpoint for other cases
                        $checkpointToUse = $endCheckpoint;
                    }
                } else {
                    // If no task found, default to end checkpoint
                    $checkpointToUse = $endCheckpoint;
                }
                
                $locationID = $checkpointToUse->locationID;
                $location = Location::find($locationID);
                
                if (!$location || $location->location_type !== 'slaughterhouse') continue;

                if (!isset($groupedData[$locationID])) {
                    $groupedData[$locationID] = [
                        'locationID' => $locationID,
                        'company_address' => $location->company_address ?? 'Unknown Location',
                        'orders' => []
                    ];
                }
                
                $orderID = $trip->orderID;
                
                if (!isset($groupedData[$locationID]['orders'][$orderID])) {
                    $order = Order::find($orderID);
                    $status = $order ? $order->order_status : 'unknown';
                    
                    $groupedData[$locationID]['orders'][$orderID] = [
                        'orderID' => $orderID,
                        'status' => $status,
                        'items' => [],
                        'trips' => []
                    ];
                }
                
                // Add trip info
                $groupedData[$locationID]['orders'][$orderID]['trips'][] = [
                    'tripID' => $trip->tripID,
                    'phase' => $item['phase'],
                    'status' => $item['status'],
                    'start_checkID' => $trip->start_checkID,
                    'end_checkID' => $trip->end_checkID,
                    // Add start location information
                    'startLocationID' => $startCheckpoint->locationID,
                    'startLocationName' => $startCheckpoint->location ? $startCheckpoint->location->company_address : 'Unknown',
                    'startLocationType' => $startCheckpoint->location ? $startCheckpoint->location->location_type : 'Unknown',
                    // Add end location information
                    'endLocationID' => $endCheckpoint->locationID,
                    'endLocationName' => $endCheckpoint->location ? $endCheckpoint->location->company_address : 'Unknown',
                    'endLocationType' => $endCheckpoint->location ? $endCheckpoint->location->location_type : 'Unknown'
                ];
                
                // Fix the redundancy issue with items
                // Only add items that aren't already in the array
                foreach ($item['items'] as $itemData) {
                    // Check if this item already exists in the items array
                    $itemExists = false;
                    foreach ($groupedData[$locationID]['orders'][$orderID]['items'] as $existingItem) {
                        if ($existingItem['itemID'] === $itemData['itemID']) {
                            $itemExists = true;
                            break;
                        }
                    }
                    
                    // Only add the item if it doesn't already exist
                    if (!$itemExists) {
                        $groupedData[$locationID]['orders'][$orderID]['items'][] = $itemData;
                    }
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
            Log::error('Error fetching delivery index: ' . $e->getMessage(), [
                'file' => $e->getFile(), // Added file and line
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString() // Log stack trace for detailed debugging
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching delivery data.', // Generic message
                'error' => $e->getMessage() // Include error message
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
     * Assign a delivery to a driver and vehicle
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
     public function assignDelivery(Request $request)
    {
        try {
            // Log the request data
            Log::info('Delivery assignment request received', [
                'request_data' => $request->all()
            ]);
            
            // Validate request - simplified to just require deliveryID and trips
            $validated = $request->validate([
                'deliveryID' => 'required|exists:deliveries,deliveryID',
                'trips' => 'required|array',
                'trips.*.tripID' => 'required|exists:trips,tripID'
            ]);
    
            // Begin transaction
            DB::beginTransaction();
    
            // Get the delivery
            $delivery = Delivery::findOrFail($validated['deliveryID']);
            Log::info('Using provided delivery', ['deliveryID' => $delivery->deliveryID]);
            
            // Update the trips with the deliveryID
            foreach ($validated['trips'] as $tripData) {
                $trip = Trip::findOrFail($tripData['tripID']);
                $trip->deliveryID = $delivery->deliveryID;
                $trip->save();
                
                Log::info('Trip updated with deliveryID', [
                    'tripID' => $trip->tripID,
                    'deliveryID' => $delivery->deliveryID
                ]);
            }
            
            // Commit the transaction
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Delivery assigned successfully',
                'data' => [
                    'deliveryID' => $delivery->deliveryID,
                    'trips' => $validated['trips']
                ]
            ]);
            
        } catch (\Exception $e) {
            // Roll back the transaction in case of error
            DB::rollBack();
            
            Log::error('Error assigning delivery: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign delivery: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all vehicles
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getVehicles(Request $request)
    {
        try {
            $query = Vehicle::query();
            
            // Add null check for user and companyID
            $user = auth()->user();
            if (!$user || !$user->companyID) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated or missing company ID'
                ], 401);
            }
            
            $query->where('companyID', $user->companyID);
            
            // Get the scheduled date from the request
            $scheduledDate = $request->input('scheduled_date');
            
            // If scheduled date is provided, filter out vehicles that are already assigned to deliveries on that date
            if ($scheduledDate) {
                // Get IDs of vehicles that are already assigned to deliveries on the scheduled date
                // and where the delivery is not completed (end_timestamp is null)

                $assignedVehicleIds = Delivery::whereDate('scheduled_date', '=', $scheduledDate)
                    ->whereNull('end_timestamp')
                    ->pluck('vehicleID')
                    ->toArray();
                
                // Exclude these vehicles from the results
                if (!empty($assignedVehicleIds)) {
                    $query->whereNotIn('vehicleID', $assignedVehicleIds);
                }
            }
            
            // Search by vehicle plate
            if ($request->has('search') && !empty($request->search)) {
                $query->where('vehicle_plate', 'like', '%' . $request->search . '%');
            }
            
            // Filter by load weight
            if ($request->has('min_weight') && is_numeric($request->min_weight)) {
                $query->where('vehicle_load_weight', '>=', $request->min_weight);
            }
            
            if ($request->has('max_weight') && is_numeric($request->max_weight)) {
                $query->where('vehicle_load_weight', '<=', $request->max_weight);
            }
            
            // Select specific fields
            $query->select('vehicleID', 'vehicle_plate', 'vehicle_load_weight');
            
            // Get all vehicles without pagination
            $vehicles = $query->get();
            
            return response()->json([
                'success' => true,
                'data' => $vehicles
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching vehicles: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vehicles: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all drivers
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getDrivers(Request $request)
    {
        try {
            // Get the authenticated user
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            // Get the company ID of the authenticated user
            $companyID = $user->companyID;
            
            // Get the scheduled date from the request
            $scheduledDate = $request->input('scheduled_date');
            
            // Get all active employees from the same company who can be drivers
            $driversQuery = User::where('role', 'employee')
                ->where('status', 'active')
                ->where('companyID', $companyID);
            
            // If scheduled date is provided, filter out drivers who are already assigned to deliveries on that date
            if ($scheduledDate) {
                // Get IDs of drivers who are already assigned to deliveries on the scheduled date
                // and where the delivery is not completed (end_timestamp is null)
                $assignedDriverIds = Delivery::whereDate('scheduled_date', '=', $scheduledDate)
                    ->pluck('userID')
                    ->toArray();
                
                // Exclude these drivers from the results
                if (!empty($assignedDriverIds)) {
                    $driversQuery->whereNotIn('userID', $assignedDriverIds);
                }
            }
            
            $drivers = $driversQuery->select('userID', 'fullname', 'tel_number', 'email')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $drivers,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching drivers: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch drivers: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create a new delivery based on Delivery model
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createDelivery(Request $request)
    {
        try {
            // Log the request data
            Log::info('Create delivery request received', [
                'request_data' => $request->all()
            ]);
            
            // Validate request
            $validated = $request->validate([
                'userID' => 'required|exists:users,userID',
                'vehicleID' => 'required|exists:vehicles,vehicleID',
                'scheduled_date' => 'required|date|after_or_equal:today',
                'start_timestamp' => 'nullable|date',
                'end_timestamp' => 'nullable|date',
            ]);
            
            // Begin transaction
            DB::beginTransaction();
            
            // Create new delivery
            $delivery = new Delivery();
            $delivery->userID = $validated['userID'];
            $delivery->vehicleID = $validated['vehicleID'];
            $delivery->scheduled_date = $validated['scheduled_date'];
            $delivery->start_timestamp = $validated['start_timestamp'] ?? null;
            $delivery->end_timestamp = $validated['end_timestamp'] ?? null;
            $delivery->save();
            
            // Get driver and vehicle details for response
            $driver = User::find($validated['userID']);
            $vehicle = Vehicle::find($validated['vehicleID']);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Delivery created successfully',
                'data' => [
                    'deliveryID' => $delivery->deliveryID,
                    'scheduledDate' => $delivery->scheduled_date,
                    'startTimestamp' => $delivery->start_timestamp,
                    'arriveTimestamp' => $delivery->end_timestamp,
                    'driver' => [
                        'userID' => $driver->userID,
                        'name' => $driver->name
                    ],
                    'vehicle' => [
                        'vehicleID' => $vehicle->vehicleID,
                        'vehicle_number' => $vehicle->vehicle_number,
                        'vehicle_type' => $vehicle->vehicle_type
                    ],
                    'status' => $delivery->end_timestamp ? 'completed' : 
                               ($delivery->start_timestamp ? 'in_progress' : 'scheduled')
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating delivery: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create delivery: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create a new trip
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createTrips(Request $request)
    {
        try {
            // Log the request data
            Log::info('Create trip request received', [
                'request_data' => $request->all()
            ]);
            
            // Validate request
            $validated = $request->validate([
                'userID' => 'required|exists:users,userID',
                'vehicleID' => 'required|exists:vehicles,vehicleID',
                'fromLocation' => 'required|exists:locations,locationID',
                'toLocation' => 'required|exists:locations,locationID|different:fromLocation',
                'scheduledDate' => 'required|date|after_or_equal:today',
                'notes' => 'nullable|string',
            ]);
            
            // Begin transaction
            DB::beginTransaction();
            
            // Create new delivery
            $delivery = new Delivery();
            $delivery->userID = $validated['userID'];
            $delivery->vehicleID = $validated['vehicleID'];
            $delivery->scheduled_date = $validated['scheduledDate'];
            $delivery->start_timestamp = null;
            $delivery->end_timestamp = null;
            $delivery->save();
            
            // Get location details for response
            $fromLocation = Location::find($validated['fromLocation']);
            $toLocation = Location::find($validated['toLocation']);
            
            // Get driver and vehicle details for response
            $driver = User::find($validated['userID']);
            $vehicle = Vehicle::find($validated['vehicleID']);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Trip created successfully',
                'data' => [
                    'deliveryID' => $delivery->deliveryID,
                    'scheduledDate' => $delivery->scheduled_date,
                    'driver' => [
                        'userID' => $driver->userID,
                        'name' => $driver->name
                    ],
                    'vehicle' => [
                        'vehicleID' => $vehicle->vehicleID,
                        'vehicle_number' => $vehicle->vehicle_number,
                        'vehicle_type' => $vehicle->vehicle_type
                    ],
                    'from' => [
                        'locationID' => $fromLocation->locationID,
                        'company_address' => $fromLocation->company_address
                    ],
                    'to' => [
                        'locationID' => $toLocation->locationID,
                        'company_address' => $toLocation->company_address
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating trip: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create trip: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get created deliveries
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getCreatedDeliveries(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 3);
            $page = $request->input('page', 1);
            
            $deliveries = Delivery::with(['user', 'vehicle', 'verifies'])
                ->whereDate('scheduled_date', '>=', now()->format('Y-m-d'))  // Only future or today's deliveries
                ->whereNull('end_timestamp')  // Exclude completed deliveries
                ->whereNull('start_timestamp')  // Exclude in-progress deliveries
                ->orderBy('scheduled_date', 'asc')  // Sort by nearest date first
                ->paginate($perPage);
                
            $formattedDeliveries = $deliveries->map(function($delivery) {
                // Get from and to locations from trips if available
                $fromLocation = null;
                $toLocation = null;
                $driver = null;
                $vehicle = null;
                $status = 'pending';
                
                // First try to get driver and vehicle directly from delivery
                if ($delivery->user) {
                    $driver = [
                        'userID' => $delivery->user->userID,
                        'fullname' => $delivery->user->fullname
                    ];
                }
                
                if ($delivery->vehicle) {
                    $vehicle = [
                        'vehicleID' => $delivery->vehicle->vehicleID,
                        'vehicle_plate' => $delivery->vehicle->vehicle_plate
                    ];
                }
                
                // Get trips associated with this delivery
                $trips = Trip::where('deliveryID', $delivery->deliveryID)
                    ->with(['startCheckpoint.location', 'endCheckpoint.location'])
                    ->get();
                
                // If trips exist, use them for from/to locations
                if ($trips->isNotEmpty()) {
                    // Get first trip for from location
                    $firstTrip = $trips->first();
                    if ($firstTrip && $firstTrip->startCheckpoint && $firstTrip->startCheckpoint->location) {
                        $fromLocation = [
                            'locationID' => $firstTrip->startCheckpoint->locationID,
                            'company_address' => $firstTrip->startCheckpoint->location->company_address ?? 'Unknown'
                        ];
                    }
                    
                    // Get last trip for to location
                    $lastTrip = $trips->last();
                    if ($lastTrip && $lastTrip->endCheckpoint && $lastTrip->endCheckpoint->location) {
                        $toLocation = [
                            'locationID' => $lastTrip->endCheckpoint->locationID,
                            'company_address' => $lastTrip->endCheckpoint->location->company_address ?? 'Unknown'
                        ];
                    }
                }
                
                // Fallback to verifies if no driver/vehicle found or no trips
                if ((!$driver || !$vehicle || !$fromLocation || !$toLocation) && $delivery->verifies->isNotEmpty()) {
                    // Get driver from verifies if not already set
                    if (!$driver) {
                        $verifyWithDriver = $delivery->verifies->first(function($verify) {
                            return $verify->user !== null;
                        });
                        
                        if ($verifyWithDriver && $verifyWithDriver->user) {
                            $driver = [
                                'userID' => $verifyWithDriver->user->userID,
                                'fullname' => $verifyWithDriver->user->fullname
                            ];
                        }
                    }
                    
                    // Get vehicle from verifies if not already set
                    if (!$vehicle) {
                        $verifyWithVehicle = $delivery->verifies->first(function($verify) {
                            return $verify->vehicle !== null;
                        });
                        
                        if ($verifyWithVehicle && $verifyWithVehicle->vehicle) {
                            $vehicle = [
                                'vehicleID' => $verifyWithVehicle->vehicle->vehicleID,
                                'vehicle_plate' => $verifyWithVehicle->vehicle->vehicle_plate
                            ];
                        }
                    }
                    
                    // Get from/to locations from verifies if not already set
                    if (!$fromLocation && $delivery->verifies->isNotEmpty()) {
                        $firstVerify = $delivery->verifies->first();
                        if ($firstVerify && $firstVerify->checkpoint && $firstVerify->checkpoint->location) {
                            $fromLocation = [
                                'locationID' => $firstVerify->checkpoint->locationID,
                                'company_address' => $firstVerify->checkpoint->location->company_address ?? 'Unknown'
                            ];
                        }
                    }
                    
                    if (!$toLocation && $delivery->verifies->isNotEmpty()) {
                        $lastVerify = $delivery->verifies->last();
                        if ($lastVerify && $lastVerify->checkpoint && $lastVerify->checkpoint->location) {
                            $toLocation = [
                                'locationID' => $lastVerify->checkpoint->locationID,
                                'company_address' => $lastVerify->checkpoint->location->company_address ?? 'Unknown'
                            ];
                        }
                    }
                }
                
                // Determine status
                if ($delivery->end_timestamp) {
                    $status = 'completed';
                } else if ($delivery->start_timestamp) {
                    $status = 'in_progress';
                }
                
                return [
                    'deliveryID' => $delivery->deliveryID,
                    'scheduledDate' => $delivery->scheduled_date,
                    'startTimestamp' => $delivery->start_timestamp,
                    'arriveTimestamp' => $delivery->end_timestamp,
                    'from' => $fromLocation,
                    'to' => $toLocation,
                    'driver' => $driver,
                    'vehicle' => $vehicle,
                    'status' => $status,
                    'created_at' => $delivery->created_at,
                    'trips' => $trips->map(function($trip) {
                        return [
                            'tripID' => $trip->tripID,
                            'startCheckpoint' => [
                                'checkID' => $trip->start_checkID,
                                'locationID' => $trip->startCheckpoint ? $trip->startCheckpoint->locationID : null,
                                'address' => $trip->startCheckpoint && $trip->startCheckpoint->location ? 
                                    $trip->startCheckpoint->location->company_address : 'Unknown'
                            ],
                            'endCheckpoint' => [
                                'checkID' => $trip->end_checkID,
                                'locationID' => $trip->endCheckpoint ? $trip->endCheckpoint->locationID : null,
                                'address' => $trip->endCheckpoint && $trip->endCheckpoint->location ? 
                                    $trip->endCheckpoint->location->company_address : 'Unknown'
                            ]
                        ];
                    })
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedDeliveries,
                'pagination' => [
                    'current_page' => $deliveries->currentPage(),
                    'last_page' => $deliveries->lastPage(),
                    'per_page' => $deliveries->perPage(),
                    'total' => $deliveries->total()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching created deliveries: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch created deliveries: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get delivery statistics
     *
     * @return \Illuminate\Http\Response
     */
    public function getDeliveryStats()
    {
        try {
            $stats = [
                'pending' => Delivery::whereNull('start_timestamp')->count(),
                'inProgress' => Delivery::whereNotNull('start_timestamp')
                    ->whereNull('end_timestamp')->count(),
                'completedToday' => Delivery::whereNotNull('end_timestamp')
                    ->whereDate('end_timestamp', now()->toDateString())->count(),
                'issues' => Verify::where('verify_status', 'rejected')->count()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching delivery stats: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch delivery stats: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get delivery details
     *
     * @param  int  $deliveryID
     * @return \Illuminate\Http\Response
     */
    public function getDeliveryDetails($deliveryID)
    {
        try {
            $delivery = Delivery::with(['verifies', 'verifies.checkpoint.location'])
                ->findOrFail($deliveryID);
                
            // Format delivery data
            $fromLocation = null;
            $toLocation = null;
            $driver = null;
            $vehicle = null;
            $status = 'pending';
            $checkpoints = [];
            
            // Check if delivery has verifications
            if ($delivery->verifies->isNotEmpty()) {
                // Get the first verify for from location
                $firstVerify = $delivery->verifies->first();
                if ($firstVerify && $firstVerify->checkpoint) {
                    $fromLocation = [
                        'locationID' => $firstVerify->checkpoint->locationID,
                        'company_address' => $firstVerify->checkpoint->location ? 
                            $firstVerify->checkpoint->location->company_address : 'Unknown'
                    ];
                }
                
                // Get the last verify for to location
                $lastVerify = $delivery->verifies->last();
                if ($lastVerify && $lastVerify->checkpoint) {
                    $toLocation = [
                        'locationID' => $lastVerify->checkpoint->locationID,
                        'company_address' => $lastVerify->checkpoint->location ? 
                            $lastVerify->checkpoint->location->company_address : 'Unknown'
                    ];
                }
                
                // Get driver and vehicle from any verify
                $verifyWithDriver = $delivery->verifies->first(function($verify) {
                    return $verify->user !== null;
                });
                
                if ($verifyWithDriver) {
                    $driver = [
                        'userID' => $verifyWithDriver->user->userID,
                        'name' => $verifyWithDriver->user->fullname
                    ];
                }
                
                $verifyWithVehicle = $delivery->verifies->first(function($verify) {
                    return $verify->vehicle !== null;
                });
                
                if ($verifyWithVehicle) {
                    $vehicle = [
                        'vehicleID' => $verifyWithVehicle->vehicle->vehicleID,
                        'vehicle_plate' => $verifyWithVehicle->vehicle->vehicle_plate
                    ];
                }
                
                // Determine status
                if ($delivery->end_timestamp) {
                    $status = 'completed';
                } else if ($delivery->start_timestamp) {
                    $status = 'in_progress';
                }
                
                // Format checkpoints
                foreach ($delivery->verifies as $verify) {
                    if ($verify->checkpoint) {
                        $checkpoints[] = [
                            'checkID' => $verify->checkpoint->checkID,
                            'locationID' => $verify->checkpoint->locationID,
                            'location' => $verify->checkpoint->location ? 
                                $verify->checkpoint->location->company_address : 'Unknown',
                            'arrange_number' => $verify->checkpoint->arrange_number,
                            'verify_status' => $verify->verify_status,
                            'verify_comment' => $verify->verify_comment,
                            'verified_at' => $verify->updated_at
                        ];
                    }
                }
            }
            
            $formattedDelivery = [
                'deliveryID' => $delivery->deliveryID,
                'scheduledDate' => $delivery->scheduled_date,
                'startTimestamp' => $delivery->start_timestamp,
                'arriveTimestamp' => $delivery->end_timestamp,
                'from' => $fromLocation,
                'to' => $toLocation,
                'driver' => $driver,
                'vehicle' => $vehicle,
                'status' => $status,
                'checkpoints' => $checkpoints,
                'created_at' => $delivery->created_at
            ];
            
            return response()->json([
                'success' => true,
                'data' => $formattedDelivery
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching delivery details: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch delivery details: ' . $e->getMessage()
            ], 500);
        }
    }
}