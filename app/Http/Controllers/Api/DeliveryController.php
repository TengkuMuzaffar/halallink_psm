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
            // Add request logging
            Log::info('Delivery index request received', [
                'locationID' => $request->input('locationID'),
                'per_page' => $request->input('per_page', 10),
                'page' => $request->input('page', 1)
            ]);
            
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
                'cartItems.item.poultry',
                'cartItems.item.location',
                'checkpoints.task', // Include tasks for checkpoints
                'checkpoints.verifies', // Include verifies for checkpoints
                'checkpoints' => function($query) {
                    $query->whereIn('arrange_number', [1, 2, 3, 4]); // Get all relevant arrange numbers
                }
            ])
            ->orderBy('created_at', 'asc') // Changed from 'desc' to 'asc' to show oldest first
            ->paginate($perPage);
            
            // Log order count
            Log::info('Orders fetched', [
                'total_orders' => $paginatedOrders->total(),
                'current_page' => $paginatedOrders->currentPage(),
                'per_page' => $paginatedOrders->perPage()
            ]);
            
            // Initialize the result array
            $groupedData = [];
            
            // Process each order
            foreach ($paginatedOrders as $order) {
                // Skip orders without cart items
                if ($order->cartItems->isEmpty()) {
                    Log::info('Skipping order with no cart items', ['orderID' => $order->orderID]);
                    continue;
                }
                
                // Get all checkpoints for this order
                $checkpoints = $order->checkpoints;
                
                // Get checkpoints by arrange_number
                $checkpoint1List = $checkpoints->where('arrange_number', 1);
                $checkpoint2List = $checkpoints->where('arrange_number', 2);
                $checkpoint3List = $checkpoints->where('arrange_number', 3);
                $checkpoint4List = $checkpoints->where('arrange_number', 4);
                
                // Process each cart item
                foreach ($order->cartItems as $cartItem) {
                    if (!$cartItem->item) {
                        Log::info('Skipping cart item with no associated item', [
                            'cartID' => $cartItem->cartID, 
                            'orderID' => $order->orderID
                        ]);
                        continue; // Skip if item doesn't exist
                    }
                    
                    $item = $cartItem->item;
                    
                    // Find matching checkpoint1 for this item (match item's locationID with checkpoint locationID)
                    $checkpoint1 = null;
                    foreach ($checkpoint1List as $cp) {
                        if ($cp->locationID == $item->locationID) {
                            $checkpoint1 = $cp;
                            break;
                        }
                    }
                    
                    // Find matching checkpoint2 for this item (match item's slaughterhouse_locationID with checkpoint locationID)
                    $checkpoint2 = null;
                    foreach ($checkpoint2List as $cp) {
                        if ($cp->locationID == $item->slaughterhouse_locationID) {
                            $checkpoint2 = $cp;
                            break;
                        }
                    }
                    
                    // Find matching checkpoint3 for this item (same logic as checkpoint2)
                    $checkpoint3 = null;
                    foreach ($checkpoint3List as $cp) {
                        if ($cp->locationID == $item->slaughterhouse_locationID) {
                            $checkpoint3 = $cp;
                            break;
                        }
                    }
                    
                    // Find matching checkpoint4 for this item (match with customer location)
                    $checkpoint4 = null;
                    foreach ($checkpoint4List as $cp) {
                        if ($cp->locationID == $order->locationID) {
                            $checkpoint4 = $cp;
                            break;
                        }
                    }
                    
                    // Log checkpoint information
                    Log::info('Checkpoint status for order and item', [
                        'orderID' => $order->orderID,
                        'itemID' => $item->itemID,
                        'checkpoint1' => $checkpoint1 ? $checkpoint1->checkID : 'null',
                        'checkpoint2' => $checkpoint2 ? $checkpoint2->checkID : 'null',
                        'checkpoint3' => $checkpoint3 ? $checkpoint3->checkID : 'null',
                        'checkpoint4' => $checkpoint4 ? $checkpoint4->checkID : 'null'
                    ]);
                    
                    // Check if we should display this item based on delivery and verification status
                    $shouldDisplay = false;
                    $useCheckpoint3 = false;
                    
                    // Modified display logic: Display if checkpoint has no deliveryID OR all verifies are complete
                    if ($checkpoint1 && $checkpoint2) {
                        // Case 1: If either checkpoint1 or checkpoint2 doesn't have deliveryID, display it
                        if (!$checkpoint1->deliveryID || !$checkpoint2->deliveryID) {
                            $shouldDisplay = true;
                            $useCheckpoint3 = false;
                            
                            Log::info('Displaying item - at least one checkpoint has no deliveryID', [
                                'orderID' => $order->orderID,
                                'itemID' => $item->itemID,
                                'checkpoint1_deliveryID' => $checkpoint1->deliveryID,
                                'checkpoint2_deliveryID' => $checkpoint2->deliveryID
                            ]);
                        } 
                        // Case 2: Both checkpoints have deliveryID, check if all verifications are complete
                        else {
                            // Check if all verifications for checkpoint1 are complete
                            $checkpoint1VerificationsComplete = true;
                            if ($checkpoint1->verifies->isNotEmpty()) {
                                foreach ($checkpoint1->verifies as $verify) {
                                    if ($verify->verify_status !== 'verified') {
                                        $checkpoint1VerificationsComplete = false;
                                        break;
                                    }
                                }
                            } else {
                                // If no verifications exist, consider it incomplete
                                $checkpoint1VerificationsComplete = false;
                            }
                            
                            // Check if all verifications for checkpoint2 are complete
                            $checkpoint2VerificationsComplete = true;
                            if ($checkpoint2->verifies->isNotEmpty()) {
                                foreach ($checkpoint2->verifies as $verify) {
                                    if ($verify->verify_status !== 'verified') {
                                        $checkpoint2VerificationsComplete = false;
                                        break;
                                    }
                                }
                            } else {
                                // If no verifications exist, consider it incomplete
                                $checkpoint2VerificationsComplete = false;
                            }
                            
                            // If both checkpoints have complete verifications, display for arrange_number 3 and 4
                            if ($checkpoint1VerificationsComplete && $checkpoint2VerificationsComplete) {
                                $shouldDisplay = true;
                                $useCheckpoint3 = true;
                                
                                Log::info('Displaying item for arrange_number 3 and 4 - all verifications complete', [
                                    'orderID' => $order->orderID,
                                    'itemID' => $item->itemID,
                                    'checkpoint1_verifies' => $checkpoint1->verifies->count(),
                                    'checkpoint2_verifies' => $checkpoint2->verifies->count()
                                ]);
                            } else {
                                Log::info('Not displaying item - verifications incomplete', [
                                    'orderID' => $order->orderID,
                                    'itemID' => $item->itemID,
                                    'checkpoint1_verifies_complete' => $checkpoint1VerificationsComplete,
                                    'checkpoint2_verifies_complete' => $checkpoint2VerificationsComplete
                                ]);
                            }
                        }
                    }
                    
                    // If we shouldn't display this item, skip to the next one
                    if (!$shouldDisplay) {
                        continue;
                    }
                    
                    // Determine which locationID to use
                    $targetLocationID = null;
                    if ($useCheckpoint3) {
                        // For arrange_number 3 and 4, use checkpoint3's locationID or item's slaughterhouse_locationID
                        $targetLocationID = $checkpoint3 ? $checkpoint3->locationID : $item->slaughterhouse_locationID;
                    } else {
                        // For arrange_number 1 and 2, use checkpoint2's locationID or item's slaughterhouse_locationID
                        $targetLocationID = $checkpoint2 ? $checkpoint2->locationID : $item->slaughterhouse_locationID;
                    }
                    
                    // If no valid locationID found, skip
                    if (!$targetLocationID) {
                        continue;
                    }
                    
                    // If filtering by locationID and it doesn't match, skip
                    if ($locationID && $targetLocationID != $locationID) {
                        continue;
                    }
                    
                    // Get location details
                    $location = Location::find($targetLocationID);
                    if (!$location) {
                        continue; // Skip if location not found
                    }
                    
                    // Determine from and to locations
                    $fromLocationDetails = null;
                    $toLocationDetails = null;
                    
                    if (!$useCheckpoint3) {
                        // For arrange_number 1 and 2
                        if ($checkpoint1) {
                            $fromLoc = Location::find($checkpoint1->locationID);
                            if ($fromLoc) {
                                $fromLocationDetails = [
                                    'locationID' => $fromLoc->locationID,
                                    'company_address' => $fromLoc->company_address
                                ];
                            }
                        } else if ($item->location) {
                            // Fallback to item's location if checkpoint1 doesn't exist
                            $fromLocationDetails = [
                                'locationID' => $item->location->locationID,
                                'company_address' => $item->location->company_address
                            ];
                        }
                        
                        if ($checkpoint2) {
                            $toLoc = Location::find($checkpoint2->locationID);
                            if ($toLoc) {
                                $toLocationDetails = [
                                    'locationID' => $toLoc->locationID,
                                    'company_address' => $toLoc->company_address
                                ];
                            }
                        } else if ($item->slaughterhouse) {
                            // Fallback to item's slaughterhouse if checkpoint2 doesn't exist
                            $toLocationDetails = [
                                'locationID' => $item->slaughterhouse->locationID,
                                'company_address' => $item->slaughterhouse->company_address
                            ];
                        }
                    } else {
                        // For arrange_number 3 and 4
                        if ($checkpoint3) {
                            $fromLoc = Location::find($checkpoint3->locationID);
                            if ($fromLoc) {
                                $fromLocationDetails = [
                                    'locationID' => $fromLoc->locationID,
                                    'company_address' => $fromLoc->company_address
                                ];
                            }
                        } else if ($item->slaughterhouse) {
                            // Fallback to item's slaughterhouse if checkpoint3 doesn't exist
                            $fromLocationDetails = [
                                'locationID' => $item->slaughterhouse->locationID,
                                'company_address' => $item->slaughterhouse->company_address
                            ];
                        }
                        
                        if ($checkpoint4) {
                            $toLoc = Location::find($checkpoint4->locationID);
                            if ($toLoc) {
                                $toLocationDetails = [
                                    'locationID' => $toLoc->locationID,
                                    'company_address' => $toLoc->company_address
                                ];
                            }
                        } else if ($order->location) {
                            // Fallback to order's location if checkpoint4 doesn't exist
                            $toLocationDetails = [
                                'locationID' => $order->location->locationID,
                                'company_address' => $order->location->company_address
                            ];
                        }
                    }
                    
                    // Ensure we have at least a from location
                    if (!$fromLocationDetails && $order->location) {
                        // Last resort: use order's location
                        $fromLocationDetails = [
                            'locationID' => $order->location->locationID,
                            'company_address' => $order->location->company_address
                        ];
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
                        'checkID' => $useCheckpoint3 ? ($checkpoint3 ? $checkpoint3->checkID : null) : 
                                                     ($checkpoint2 ? $checkpoint2->checkID : null),
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
                        $groupedData[$targetLocationID]['orders'][$order->orderID] = [
                            'from' => $fromLocationDetails,
                            'to' => $toLocationDetails,
                            'items' => []
                        ];
                    }
                    
                    // Add item to the order
                    $groupedData[$targetLocationID]['orders'][$order->orderID]['items'][] = $itemData;
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
            
            // Validate request
            $validated = $request->validate([
                'locationID' => 'required|exists:locations,locationID',
                'orderID' => 'required|exists:orders,orderID',
                'userID' => 'required|exists:users,userID',
                'vehicleID' => 'required|exists:vehicles,vehicleID',
                'scheduledDate' => 'required|date',
                // Make fromLocation and toLocation optional
                'fromLocation' => 'nullable|exists:locations,locationID',
                'toLocation' => 'nullable|exists:locations,locationID',
            ]);
    
            // Begin transaction
            DB::beginTransaction();
    
            // Get the order with its checkpoints and verifies
            $order = Order::with(['checkpoints.verifies', 'cartItems.item'])->findOrFail($validated['orderID']);
            
            Log::info('Order fetched for delivery assignment', [
                'orderID' => $order->orderID,
                'order_status' => $order->order_status,
                'checkpoints_count' => $order->checkpoints->count(),
                'cart_items_count' => $order->cartItems->count()
            ]);
            
            // Use provided fromLocation and toLocation if available, otherwise determine from order
            $fromLocationID = $validated['fromLocation'] ?? $validated['locationID'];
            
            // If toLocation is provided, use it, otherwise determine from the first cart item
            if (isset($validated['toLocation'])) {
                $toLocationID = $validated['toLocation'];
            } else {
                // Get the first cart item to determine the slaughterhouse location
                $cartItem = $order->cartItems->first();
                if (!$cartItem) {
                    Log::warning('No cart items found for order', ['orderID' => $validated['orderID']]);
                    return response()->json([
                        'success' => false,
                        'message' => 'No items found for this order'
                    ], 404);
                }
                
                $item = Item::findOrFail($cartItem->itemID);
                $toLocationID = $item->slaughterhouse_locationID;
            }
            
            Log::info('Using locations for delivery', [
                'fromLocationID' => $fromLocationID,
                'toLocationID' => $toLocationID
            ]);
            
            // Rest of the method remains the same...
    
            // Check for existing delivery that can be reused (start_timestamp is null)
            $existingDelivery = Delivery::whereNull('start_timestamp')
                ->whereHas('verifies', function($query) use ($validated) {
                    $query->where('userID', $validated['userID'])
                          ->where('vehicleID', $validated['vehicleID']);
                })
                ->first();
            
            // Create new delivery if no reusable one exists
            if (!$existingDelivery) {
                $delivery = new Delivery();
                $delivery->scheduled_date = $validated['scheduledDate'];
                $delivery->start_timestamp = null;
                $delivery->arrive_timestamp = null;
                $delivery->save();
                
                Log::info('New delivery created', ['deliveryID' => $delivery->deliveryID]);
            } else {
                $delivery = $existingDelivery;
                Log::info('Reusing existing delivery', ['deliveryID' => $delivery->deliveryID]);
            }
            
            // Get checkpoints for this order
            $checkpoints = Checkpoint::where('orderID', $validated['orderID'])->get();
            
            Log::info('Checkpoints for order', [
                'orderID' => $validated['orderID'],
                'checkpoints_count' => $checkpoints->count(),
                'checkpoint_ids' => $checkpoints->pluck('checkID')->toArray(),
                'arrange_numbers' => $checkpoints->pluck('arrange_number')->toArray()
            ]);
            
            // Determine which checkpoints to use (1-2 or 3-4)
            $checkpoint1 = $checkpoints->where('arrange_number', 1)->first();
            $checkpoint2 = $checkpoints->where('arrange_number', 2)->first();
            $useFirstPair = true; // Default to using checkpoints 1 and 2
            
            // Check if checkpoints 1 and 2 are verified
            if ($checkpoint1 && $checkpoint2) {
                // Get verifications for checkpoint 1 and 2
                $checkpoint1Verifies = Verify::where('checkID', $checkpoint1->checkID)
                    ->where('verify_status', 'verified')
                    ->count();
                    
                $checkpoint2Verifies = Verify::where('checkID', $checkpoint2->checkID)
                    ->where('verify_status', 'verified')
                    ->count();
                
                Log::info('Checkpoint verification counts', [
                    'checkpoint1_id' => $checkpoint1->checkID,
                    'checkpoint1_verified_count' => $checkpoint1Verifies,
                    'checkpoint2_id' => $checkpoint2->checkID,
                    'checkpoint2_verified_count' => $checkpoint2Verifies
                ]);
                
                // If both checkpoints are verified, use checkpoints 3 and 4
                if ($checkpoint1Verifies > 0 && $checkpoint2Verifies > 0) {
                    $useFirstPair = false;
                    Log::info('Both checkpoints 1 and 2 are verified, using checkpoints 3 and 4');
                }
            }
            
            // If not already determined by verification status, check task completion
            if ($useFirstPair && $checkpoint2) {
                // Get the task for checkpoint 2
                $task = Task::where('checkID', $checkpoint2->checkID)->first();
                
                // If task exists for checkpoint 2 and is completed, use checkpoints 3 and 4
                if ($task && $task->finish_timestamp !== null) {
                    $useFirstPair = false;
                    Log::info('Checkpoint 2 task is completed, using checkpoints 3 and 4', [
                        'taskID' => $task->taskID,
                        'finish_timestamp' => $task->finish_timestamp
                    ]);
                }
            }
            
            // Get the appropriate checkpoints based on our decision
            $arrangeNumbers = $useFirstPair ? [1, 2] : [3, 4];
            $checkpointsToUpdate = $checkpoints->whereIn('arrange_number', $arrangeNumbers);
            
            Log::info('Selected checkpoints for update', [
                'arrange_numbers' => $arrangeNumbers,
                'checkpoints_count' => $checkpointsToUpdate->count(),
                'checkpoint_ids' => $checkpointsToUpdate->pluck('checkID')->toArray()
            ]);
            
            if ($checkpointsToUpdate->count() < 2) {
                Log::warning('Required checkpoints not found', [
                    'orderID' => $validated['orderID'],
                    'arrange_numbers' => $arrangeNumbers,
                    'checkpoints_found' => $checkpointsToUpdate->count()
                ]);
                
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Required checkpoints not found for this order'
                ], 404);
            }
            
            // Update each checkpoint with the deliveryID and create verify records
            foreach ($checkpointsToUpdate as $checkpoint) {
                // Update checkpoint with deliveryID
                $checkpoint->deliveryID = $delivery->deliveryID;
                $checkpoint->save();
                
                // Create verify record for this checkpoint
                Verify::create([
                    'userID' => $validated['userID'],
                    'checkID' => $checkpoint->checkID,
                    'vehicleID' => $validated['vehicleID'],
                    'deliveryID' => $delivery->deliveryID,
                    'verify_status' => 'pending'
                ]);
            }
            
            // Update order status to in-progress if it was pending
            if ($order->order_status === 'pending') {
                $order->order_status = 'in-progress';
                $order->save();
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Delivery assigned successfully',
                'data' => [
                    'deliveryID' => $delivery->deliveryID,
                    'checkpoints' => $checkpointsToUpdate->pluck('checkID')
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning delivery: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign delivery: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all vehicles
     *
     * @return \Illuminate\Http\Response
     */
    public function getVehicles()
    {
        try {
            $vehicles = Vehicle::where('status', 'active')
                ->select('vehicleID', 'vehicle_plate', 'vehicle_load_weight')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $vehicles
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching vehicles: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch vehicles: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all drivers
     *
     * @return \Illuminate\Http\Response
     */
    public function getDrivers()
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
            
            // Get all active employees from the same company who can be drivers
            $drivers = User::where('role', 'employee')
                ->where('status', 'active')
                ->where('companyID', $companyID)
                ->select('userID', 'fullname', 'tel_number', 'email')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $drivers
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching drivers: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch drivers: ' . $e->getMessage()
            ], 500);
        }
    }
}