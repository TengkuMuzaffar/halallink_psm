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
use Illuminate\Support\Facades\Validator;
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
                'per_page' => $request->input('per_page', 3),
                'page' => $request->input('page', 1),
                'phase' => $request->input('phase')
            ]);

            // Get pagination parameters
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            
            // Filter by locationID if provided
            $locationIDFilter = $request->input('locationID');
            
            // Filter by phase if provided (default to showing all phases)
            $phaseFilter = $request->input('phase');
            
            // Start with orders that are not complete
            $incompleteOrders = Order::where('order_status', '!=', 'complete')
                ->orderBy('created_at', 'asc') // First in, first out
                ->get();
                
            // Initialize array for trips to be displayed
            $tripsToDisplay = [];
            
            // Process each incomplete order
            foreach ($incompleteOrders as $order) {
                $orderID = $order->orderID;
                
                // Get trips for this order that don't have a deliveryID assigned yet
                $trips = Trip::where('orderID', $orderID)
                    ->whereNull('deliveryID')
                    ->with(['startCheckpoint', 'endCheckpoint'])
                    ->get();
                
                foreach ($trips as $trip) {
                    $startCheckpoint = $trip->startCheckpoint;
                    $endCheckpoint = $trip->endCheckpoint;
                    
                    // Skip if checkpoints are missing
                    if (!$startCheckpoint || !$endCheckpoint) {
                        continue;
                    }
                    
                    // Phase 1 - Trips with end checkpoint arrange_number = 2
                    if ($phaseFilter == 1 || !$phaseFilter) {
                        if ($endCheckpoint->arrange_number == 2) {
                            $tripData = [
                                'tripID' => $trip->tripID,
                                'orderID' => $trip->orderID,
                                'phase' => 1,
                                'start_checkID' => $trip->start_checkID,
                                'end_checkID' => $trip->end_checkID,
                                'start_location' => [
                                    'locationID' => $startCheckpoint->locationID,
                                    'address' => $startCheckpoint->location ? $startCheckpoint->location->company_address : 'Unknown',
                                    'type' => $startCheckpoint->location ? $startCheckpoint->location->location_type : 'Unknown'
                                ],
                                'end_location' => [
                                    'locationID' => $endCheckpoint->locationID,
                                    'address' => $endCheckpoint->location ? $endCheckpoint->location->company_address : 'Unknown',
                                    'type' => $endCheckpoint->location ? $endCheckpoint->location->location_type : 'Unknown'
                                ],
                                'items' => []
                            ];
                            
                            // Add items from checkpoint item_record
                            if ($startCheckpoint->item_record && $endCheckpoint->item_record) {
                                // Find common items between start and end checkpoints
                                $commonItems = array_intersect($startCheckpoint->item_record, $endCheckpoint->item_record);
                                
                                if (!empty($commonItems)) {
                                    $items = Item::whereIn('itemID', $commonItems)
                                        ->with(['poultry', 'location', 'slaughterhouse'])
                                        ->get();
                                    
                                    foreach ($items as $item) {
                                            $tripData['items'][] = [
                                                'itemID' => $item->itemID,
                                                'name' => $item->poultry ? $item->poultry->poultry_name : 'Unknown',
                                                'measurement_type' => $item->measurement_type,
                                                'measurement_value' => $item->measurement_value,
                                                'price' => $item->price,
                                                'is_deleted' => method_exists($item, 'trashed') && $item->trashed(),
                                                'location_deleted' => $item->location && method_exists($item->location, 'trashed') ? $item->location->trashed() : false
                                            ];
                                        }
                                }
                            }
                            
                            $tripsToDisplay[] = $tripData;
                        }
                    }
                    
                    // Phase 2 - Find trips with start_checkID arrange_number = 3
                    if ($phaseFilter == 2 || !$phaseFilter) {
                        if ($startCheckpoint->arrange_number == 3) {
                            // First, check if there's a completed task for the previous checkpoint (arrange_number = 2)
                            // Find checkpoints with arrange_number = 2 for this order
                            $previousCheckpoints = Checkpoint::where('orderID', $orderID)
                                ->where('arrange_number', 2)
                                ->get();
                            
                            foreach ($previousCheckpoints as $prevCheckpoint) {
                                // Check if there's a completed task for this checkpoint
                                $task = Task::where('checkID', $prevCheckpoint->checkID)
                                    ->where('task_status', 'completed')
                                    ->whereNotNull('start_timestamp')
                                    ->whereNotNull('finish_timestamp')
                                    ->first();
                                
                                if ($task) {
                                    // Get the target location from the previous checkpoint
                                    $targetLocationID = $prevCheckpoint->locationID;
                                    $targetArrayItem = $prevCheckpoint->item_record;

                                    // Check if current trip's start location matches the target location
                                    // AND if the item records are the same
                                    if ($startCheckpoint->locationID == $targetLocationID && $startCheckpoint->item_record == $targetArrayItem) {
                                        $tripData = [
                                            'tripID' => $trip->tripID,
                                            'orderID' => $trip->orderID,
                                            'phase' => 2,
                                            'start_checkID' => $trip->start_checkID,
                                            'end_checkID' => $trip->end_checkID,
                                            'start_location' => [
                                                'locationID' => $startCheckpoint->locationID,
                                                'address' => $startCheckpoint->location ? $startCheckpoint->location->company_address : 'Unknown',
                                                'type' => $startCheckpoint->location ? $startCheckpoint->location->location_type : 'Unknown'
                                            ],
                                            'end_location' => [
                                                'locationID' => $endCheckpoint->locationID,
                                                'address' => $endCheckpoint->location ? $endCheckpoint->location->company_address : 'Unknown',
                                                'type' => $endCheckpoint->location ? $endCheckpoint->location->location_type : 'Unknown'
                                            ],
                                            'items' => []
                                        ];
                                        
                                        // Add items from checkpoint item_record
                                        if ($startCheckpoint->item_record && $endCheckpoint->item_record) {
                                            // Find common items between start and end checkpoints
                                            $commonItems = array_intersect($startCheckpoint->item_record, $endCheckpoint->item_record);
                                            
                                            if (!empty($commonItems)) {
                                                $items = Item::whereIn('itemID', $commonItems)
                                                    ->with(['poultry', 'location', 'slaughterhouse'])
                                                    ->get();
                                                
                                                foreach ($items as $item) {
                                                    $tripData['items'][] = [
                                                        'itemID' => $item->itemID,
                                                        'name' => $item->poultry ? $item->poultry->poultry_name : 'Unknown',
                                                        'measurement_type' => $item->measurement_type,
                                                        'measurement_value' => $item->measurement_value,
                                                        'price' => $item->price,
                                                        'is_deleted' => method_exists($item, 'trashed') && $item->trashed(),
                                                        'location_deleted' => $item->location && method_exists($item->location, 'trashed') ? $item->location->trashed() : false
                                                    ];
                                                }
                                            }
                                        }
                                        
                                        $tripsToDisplay[] = $tripData;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            // Apply location filter if provided
            if ($locationIDFilter) {
                $tripsToDisplay = array_filter($tripsToDisplay, function($trip) use ($locationIDFilter) {
                    return ($trip['start_location']['locationID'] == $locationIDFilter) || 
                           ($trip['end_location']['locationID'] == $locationIDFilter);
                });
                
                // Re-index array after filtering
                $tripsToDisplay = array_values($tripsToDisplay);
            }
            
            // Manual pagination
            $total = count($tripsToDisplay);
            $paginatedTrips = array_slice($tripsToDisplay, ($page - 1) * $perPage, $perPage);
            
            $pagination = [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'from' => (($page - 1) * $perPage) + 1,
                'to' => min($page * $perPage, $total)
            ];
            
            // Count trips by phase for stats
            $phase1Count = count(array_filter($tripsToDisplay, function($trip) {
                return $trip['phase'] == 1;
            }));
            
            $phase2Count = count(array_filter($tripsToDisplay, function($trip) {
                return $trip['phase'] == 2;
            }));
            
            return response()->json([
                'success' => true,
                'data' => $paginatedTrips,
                'pagination' => $pagination,
                'phase_stats' => [
                    'phase1_count' => $phase1Count,
                    'phase2_count' => $phase2Count
                ]
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
     * Assign a single trip to a delivery
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function assignSingleTrip(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'deliveryID' => 'required|exists:deliveries,deliveryID',
                'tripID' => 'required|exists:trips,tripID'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            DB::beginTransaction();

            // Get the trip and assign it to the delivery
            $trip = Trip::findOrFail($validated['tripID']);
            $trip->deliveryID = $validated['deliveryID'];
            $trip->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Trip assigned successfully',
                'data' => [
                    'trip' => $trip
                ]
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Error assigning trip: Model not found. ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign trip: Required record not found. '. $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning trip: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign trip: ' . $e->getMessage()
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
            
            // If user is an employee, only return their own information
            if ($user->role === 'employee') {
                $drivers = User::where('userID', $user->userID)
                    ->where('status', 'active')
                    ->select('userID', 'fullname', 'tel_number', 'email')
                    ->get();
                    
                return response()->json([
                    'success' => true,
                    'data' => $drivers,
                ]);
            }
            
            // For non-employee roles, continue with existing logic
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
            
            $query = Delivery::with(['user', 'vehicle', 'verifies']);
            
            // Check if authenticated user is an employee
            if (auth()->user()->role === 'employee') {
                $query->where('userID', auth()->user()->userID);
            }
            
            $deliveries = $query
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
            // Get authenticated user
            $user = auth()->user();
            $companyID = $user->companyID;
            
            // Base query builder for Delivery
            $deliveryQuery = Delivery::query();
            $verifyQuery = Verify::query();
            
            // Apply filters based on user role
            if ($user->role === 'employee') {
                // Employee can only see their own deliveries
                $deliveryQuery->where('userID', $user->userID);
                $verifyQuery->whereHas('checkpoint.delivery', function($query) use ($user) {
                    $query->where('userID', $user->userID);
                });
            } else if ($user->role === 'admin') {
                // Admin can see all deliveries in their company
                $deliveryQuery->whereHas('user', function($query) use ($companyID) {
                    $query->where('companyID', $companyID);
                });
                $verifyQuery->whereHas('checkpoint.delivery.user', function($query) use ($companyID) {
                    $query->where('companyID', $companyID);
                });
            }
            
            $stats = [
                'pending' => (clone $deliveryQuery)
                    ->whereNull('start_timestamp')
                    ->count(),
                    
                'inProgress' => (clone $deliveryQuery)
                    ->whereNotNull('start_timestamp')
                    ->whereNull('end_timestamp')
                    ->count(),
                    
                'completedToday' => (clone $deliveryQuery)
                    ->whereNotNull('end_timestamp')
                    ->whereDate('end_timestamp', now()->toDateString())
                    ->count(),
                    
                'unassignedTrips' => \App\Models\Trip::whereNull('deliveryID')->count()
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
    
    /**
     * Delete a delivery by setting deliveryID to null for all associated trips
     *
     * @param  int  $deliveryID
     * @return \Illuminate\Http\Response
     */
     public function deleteDelivery($deliveryID)
    {
        try {
            // Find the delivery to ensure it exists
            $delivery = Delivery::findOrFail($deliveryID);
            
            // Check if the delivery has already started
            if ($delivery->start_timestamp !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a delivery that has already started'
                ], 400);
            }
            
            // Get all trips associated with this delivery
            $trips = Trip::where('deliveryID', $deliveryID)->get();
            
            // Count how many trips were updated
            $tripCount = $trips->count();
            
            if ($tripCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No trips found for this delivery'
                ], 404);
            }
            
            // Update all trips to set deliveryID to null
            Trip::where('deliveryID', $deliveryID)
                ->update(['deliveryID' => null]);
            
            // Delete the delivery record
            $delivery->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Delivery deleted successfully. {$tripCount} trips have been unassigned."
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting delivery: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete delivery: ' . $e->getMessage()
            ], 500);
        }
    }

}