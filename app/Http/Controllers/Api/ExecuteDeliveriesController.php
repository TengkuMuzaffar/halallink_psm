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
            
            // Start with a base query
            Log::info('ExecuteDeliveriesController: Building base query with user and vehicle relationships');
            $query = Delivery::with(['user', 'vehicle']);
            
            // Only include deliveries that have trips
            $query->whereHas('trips', function($q) {
                // This ensures only deliveries with at least one trip are included
                $q->whereNotNull('tripID');
            });
            
            // Apply role-based filtering
            if ($user->role === 'employee') {
                // If user is an employee, only show deliveries assigned to them
                Log::info('ExecuteDeliveriesController: Applying employee filter', [
                    'userID' => $user->userID
                ]);
                $query->where('userID', $user->userID);
            } else if ($user->role === 'admin') {
                // If user is an admin, show all deliveries from their company including admin's deliveries
                Log::info('ExecuteDeliveriesController: Applying admin filter', [
                    'companyID' => $user->companyID
                ]);
                $query->whereHas('user', function($q) use ($user) {
                    $q->where('companyID', $user->companyID);
                });
            }
            
            // Apply status filter if provided
            if ($request->has('status') && !empty($request->status)) {
                $status = $request->status;
                Log::info('ExecuteDeliveriesController: Applying status filter', [
                    'status' => $status
                ]);
                
                if ($status === 'pending') {
                    $query->whereNull('start_timestamp')->whereNull('arrive_timestamp');
                } else if ($status === 'in_progress') {
                    $query->whereNotNull('start_timestamp')->whereNull('arrive_timestamp');
                } else if ($status === 'completed') {
                    $query->whereNotNull('arrive_timestamp');
                }
            }
            
            // Apply date filter if provided
            if ($request->has('date') && !empty($request->date)) {
                Log::info('ExecuteDeliveriesController: Applying date filter', [
                    'date' => $request->date
                ]);
                $query->whereDate('scheduled_date', $request->date);
            }
            
            // Apply driver filter if provided
            if ($request->has('driver') && !empty($request->driver)) {
                Log::info('ExecuteDeliveriesController: Applying driver filter', [
                    'driverID' => $request->driver
                ]);
                $query->where('userID', $request->driver);
            }
            
            // Log the SQL query that will be executed
            $sql = $query->toSql();
            $bindings = $query->getBindings();
            Log::info('ExecuteDeliveriesController: SQL Query', [
                'sql' => $sql,
                'bindings' => $bindings
            ]);
            
            // Get the deliveries and sort by deliveryID
            Log::info('ExecuteDeliveriesController: Executing query');
            $deliveries = $query->orderBy('deliveryID')->get();
            
            // Check if deliveries exist
            Log::info('ExecuteDeliveriesController: Query returned ' . $deliveries->count() . ' deliveries');
            
            if ($deliveries->isEmpty()) {
                Log::info('ExecuteDeliveriesController: No deliveries found, returning empty array');
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No deliveries found matching the criteria'
                ]);
            }
            
            // Format the response data according to the requested structure
            Log::info('ExecuteDeliveriesController: Formatting response data');
            $formattedDeliveries = [];
            
            foreach ($deliveries as $delivery) {
                Log::info('ExecuteDeliveriesController: Processing delivery', [
                    'deliveryID' => $delivery->deliveryID
                ]);
                
                // Get all trips related to this delivery
                $trips = Trip::where('deliveryID', $delivery->deliveryID)
                    ->with(['startCheckpoint.location', 'endCheckpoint.location', 'verifications'])
                    ->get();
                
                Log::info('ExecuteDeliveriesController: Found ' . $trips->count() . ' trips for delivery', [
                    'deliveryID' => $delivery->deliveryID
                ]);
                
                // Skip deliveries with no trips (additional check)
                if ($trips->isEmpty()) {
                    Log::info('ExecuteDeliveriesController: Skipping delivery with no trips', [
                        'deliveryID' => $delivery->deliveryID
                    ]);
                    continue;
                }
                
                $formattedTrips = [];
                
                foreach ($trips as $trip) {
                    Log::info('ExecuteDeliveriesController: Processing trip', [
                        'tripID' => $trip->tripID
                    ]);
                    
                    $startCheckpoint = $trip->startCheckpoint;
                    $endCheckpoint = $trip->endCheckpoint;
                    $startLocation = $startCheckpoint ? $startCheckpoint->location : null;
                    $endLocation = $endCheckpoint ? $endCheckpoint->location : null;
                    
                    // Get order from trip if available
                    $order = $trip->order;
                    $orderID = $order ? $order->orderID : ($startCheckpoint ? $startCheckpoint->orderID : null);
                    
                    // Get cart items related to this order
                    $cartItems = [];
                    if ($orderID) {
                        $carts = Cart::where('orderID', $orderID)->with('item')->get();
                        
                        foreach ($carts as $cart) {
                            if ($cart->item) {
                                $item = $cart->item;
                                $slaughterhouseLocationID = $item->slaughterhouse_locationID;
                                $itemLocationID = $item->locationID;
                                
                                // Check if item's slaughterhouse matches start or end location
                                $matchesStartLocation = $startLocation && $startLocation->locationID == $slaughterhouseLocationID;
                                $matchesEndLocation = $endLocation && $endLocation->locationID == $slaughterhouseLocationID;
                                
                                // Only include items that match the trip's locations
                                if ($matchesStartLocation || $matchesEndLocation) {
                                    $cartItems[$item->itemID] = [
                                        'itemID' => $item->itemID,
                                        'poultryID' => $item->poultryID,
                                        'measurement_type' => $item->measurement_type,
                                        'measurement_value' => $item->measurement_value,
                                        'price' => $item->price,
                                        'quantity' => $cart->quantity,
                                        'price_at_purchase' => $cart->price_at_purchase,
                                        'slaughterhouse_locationID' => $slaughterhouseLocationID,
                                        'locationID' => $itemLocationID
                                    ];
                                }
                            }
                        }
                    }
                    
                    // Get verifications related to this trip
                    $verifications = Verify::where('deliveryID', $delivery->deliveryID)
                        ->whereIn('checkID', [$trip->start_checkID, $trip->end_checkID])
                        ->get();
                    
                    $formattedVerifies = [];
                    foreach ($verifications as $verify) {
                        $formattedVerifies[$verify->verifyID] = [
                            'verifyID' => $verify->verifyID,
                            'checkID' => $verify->checkID,
                            'verify_status' => $verify->verify_status,
                            'verify_comment' => $verify->verify_comment,
                            'created_at' => $verify->created_at
                        ];
                    }
                    
                    $formattedTrips[$trip->tripID] = [
                        'tripID' => $trip->tripID,
                        'start_checkID' => $trip->start_checkID,
                        'end_checkID' => $trip->end_checkID,
                        'start_location' => $startLocation ? [
                            'locationID' => $startLocation->locationID,
                            'company_address' => $startLocation->company_address,
                            'location_type' => $startLocation->location_type
                        ] : null,
                        'end_location' => $endLocation ? [
                            'locationID' => $endLocation->locationID,
                            'company_address' => $endLocation->company_address,
                            'location_type' => $endLocation->location_type
                        ] : null,
                        'items' => $cartItems,
                        'verifies' => $formattedVerifies
                    ];
                }
                
                // Add formatted delivery to the array
                $formattedDeliveries[$delivery->deliveryID] = [
                    'deliveryID' => $delivery->deliveryID,
                    'scheduled_date' => $delivery->scheduled_date,
                    'vehicle_plate' => $delivery->vehicle ? $delivery->vehicle->vehicle_plate : null,
                    'driver_name' => $delivery->user ? $delivery->user->fullname : null,
                    'start_timestamp' => $delivery->start_timestamp,
                    'arrive_timestamp' => $delivery->arrive_timestamp,
                    'trips' => $formattedTrips
                ];
            }
            
            Log::info('ExecuteDeliveriesController: Successfully formatted all deliveries, returning response');
            return response()->json([
                'success' => true,
                'data' => $formattedDeliveries,
                'message' => 'Execution deliveries retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('ExecuteDeliveriesController: Error fetching execution deliveries', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch execution deliveries: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}