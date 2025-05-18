<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Verify;
use App\Models\Checkpoint;
use App\Models\DeliveryLocationToken; // Import the model
use App\Models\Trip; // Import the Trip model
use App\Models\Item; // Import the Item model
use Illuminate\Support\Facades\Log;
use Carbon\Carbon; // Import Carbon for date comparison

class VerifyController extends Controller
{
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'deliveryID' => 'required|exists:deliveries,deliveryID',
                'locationID' => 'nullable|exists:locations,locationID',
                'token' => 'required|string', // Add token validation
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $deliveryID = $request->deliveryID;
            $locationID = $request->locationID;
            $token = $request->token;

            // Check if the token is valid and not expired for the given deliveryID and locationID
            $validToken = DeliveryLocationToken::where('deliveryID', $deliveryID)
                                            ->where('locationID', $locationID)
                                            ->where('token', $token)
                                            ->where('expired_at', '>', Carbon::now()) // Check if token is not expired
                                            ->first();

            if (!$validToken) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid or expired token. Access not permissible.'
                ], 403); // Use 403 Forbidden status code
            }

            // If token is valid, proceed with fetching verifications
            $query = Verify::where('deliveryID', $deliveryID)
                          ->where('verify_status', 'pending');

            // If locationID is provided, filter by checkpoints at that location
            if ($request->has('locationID')) {
                $checkpoints = Checkpoint::where('locationID', $request->locationID)->pluck('checkID');
                $query->whereIn('checkID', $checkpoints);
            }
            
            $verifications = $query->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $verifications
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching verifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching verifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get a specific verification with associated items
     *
     * @param  int  $verifyID
     * @return \Illuminate\Http\Response
     */
    public function show($verifyID, Request $request) // Add Request parameter
    {
        try {
            // Add validation for deliveryID, locationID, and token
            $validator = Validator::make($request->all(), [
                'deliveryID' => 'required|exists:deliveries,deliveryID',
                'locationID' => 'nullable|exists:locations,locationID',
                'token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $deliveryID = $request->deliveryID;
            $locationID = $request->locationID;
            $token = $request->token;

            // Check if the token is valid and not expired for the given deliveryID and locationID
            $validToken = DeliveryLocationToken::where('deliveryID', $deliveryID)
                                            ->where('locationID', $locationID)
                                            ->where('token', $token)
                                            ->where('expired_at', '>', Carbon::now()) // Check if token is not expired
                                            ->first();

            if (!$validToken) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid or expired token. Access not permissible.'
                ], 403); // Use 403 Forbidden status code
            }

            // Eager load checkpoint
            $verify = Verify::with('checkpoint')->find($verifyID);

            if (!$verify) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Verification not found'
                ], 404);
            }

            // Ensure the requested verifyID belongs to the deliveryID from the token
            if ($verify->deliveryID != $deliveryID) {
                 return response()->json([
                    'status' => 'error',
                    'message' => 'Verification ID does not match the provided Delivery ID.'
                ], 403); // Use 403 Forbidden status code
            }


            $checkID = $verify->checkID;
            $items = []; // Initialize items array

            // Find trips where this checkID is either the start or end checkpoint
            $tripAsStart = Trip::where('start_checkID', $checkID)->first();
            $tripAsEnd = Trip::where('end_checkID', $checkID)->first();

            $itemIDs = [];

            if ($tripAsStart) {
                // If checkID is a start_checkID in a trip
                $startCheckpoint = $verify->checkpoint; // Already loaded
                $endCheckpoint = Checkpoint::find($tripAsStart->end_checkID);

                if ($startCheckpoint && $endCheckpoint) {
                    $startItemRecord = $startCheckpoint->item_record ?? [];
                    $endItemRecord = $endCheckpoint->item_record ?? [];

                    // Items are those in start_checkID's item_record that are ALSO in end_checkID's item_record
                    $itemIDs = array_intersect($startItemRecord, $endItemRecord); // Changed from array_diff to array_intersect
                }
            } elseif ($tripAsEnd) {
                // If checkID is an end_checkID in a trip (and not a start_checkID)
                $endCheckpoint = $verify->checkpoint; // Already loaded

                if ($endCheckpoint) {
                    // Items are simply those in the end_checkID's item_record
                    $itemIDs = $endCheckpoint->item_record ?? [];
                }
            } else {
                 // If checkID is not associated with any trip (e.g., initial checkpoint)
                 $checkpoint = $verify->checkpoint;
                 if ($checkpoint) {
                     $itemIDs = $checkpoint->item_record ?? [];
                 }
            }


            // Fetch item details for the determined item IDs
            if (!empty($itemIDs)) {
                // Join with carts to get orderID and sort by it
                $items = Item::whereIn('items.itemID', $itemIDs)  // Specify the table name here
                             ->with('poultry') // Eager load poultry to get poultry_name
                             ->leftJoin('carts', 'items.itemID', '=', 'carts.itemID')
                             ->select('items.*', 'carts.orderID', 'carts.cartID')
                             ->orderBy('carts.orderID')
                             ->get()
                             ->map(function ($item) {
                                 return [
                                     'itemID' => $item->itemID,
                                     'item_name' => $item->poultry ? $item->poultry->poultry_name : 'Unknown Poultry',
                                     'quantity' => 1, // Assuming quantity is 1 per item ID in the record
                                     'orderID' => $item->orderID, // Include orderID in the response
                                     'cartID' => $item->cartID, // Include cartID in the response
                                 ];
                             });
            }

            // Add the items to the verification data
            $verifyData = $verify->toArray();
            $verifyData['associated_items'] = $items;

            return response()->json([
                'status' => 'success',
                'data' => $verifyData
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching verification with items', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching verification details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update a verification
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $verifyID
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $verifyID)
    {
        try {
            $validator = Validator::make($request->all(), [
                'verify_status' => 'required|in:pending,complete,rejected',
                'verify_comment' => 'nullable|string|max:500',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $verify = Verify::find($verifyID);
            
            if (!$verify) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Verification not found'
                ], 404);
            }
            
            $verify->verify_status = $request->verify_status;
            $verify->verify_comment = $request->verify_comment;
            $verify->save();
            
            // Check if all verifications are complete and update delivery if needed
            $deliveryCompleted = false;
            if ($request->verify_status === 'complete') {
                $deliveryCompleted = $this->checkAndUpdateDeliveryCompletion($verify->deliveryID);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Verification updated successfully',
                'data' => $verify,
                'delivery_completed' => $deliveryCompleted
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error updating verification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating verification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Complete all verifications for a delivery
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $deliveryID
     * @return \Illuminate\Http\Response
     */
    public function completeVerification(Request $request, $deliveryID)
    {
        try {
            // Get all verifications for this delivery
            $verifications = Verify::where('deliveryID', $deliveryID)->get();
            
            if ($verifications->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No verifications found for this delivery'
                ], 404);
            }
            
            // Check if all verifications are verified
            $allVerified = $verifications->every(function ($verify) {
                return $verify->verify_status === 'verified';
            });
            
            if (!$allVerified) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Not all verifications are marked as verified'
                ], 400);
            }
            
            // Update the delivery status if needed
            $delivery = Delivery::find($deliveryID);
            
            if ($delivery) {
                // You might want to update the delivery status here
                // $delivery->status = 'verified';
                // $delivery->save();
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'All verifications completed successfully'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error completing verification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while completing verification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check if all verifications for a delivery are complete and update end_timestamp if needed
     *
     * @param  int  $deliveryID
     * @return bool Whether all verifications are complete
     */
    private function checkAndUpdateDeliveryCompletion($deliveryID)
    {
        try {
            // Get all checkpoints associated with this delivery from trips
            $trips = Trip::where('deliveryID', $deliveryID)->get();
            
            if ($trips->isEmpty()) {
                return false; // No trips found for this delivery
            }
            
            // Collect all unique checkIDs from trips
            $checkIDs = [];
            foreach ($trips as $trip) {
                $checkIDs[] = $trip->start_checkID;
                $checkIDs[] = $trip->end_checkID;
            }
            
            // Remove duplicates
            $uniqueCheckIDs = array_unique($checkIDs);
            
            // Get all verifications for these checkpoints
            $verifications = Verify::where('deliveryID', $deliveryID)
                                  ->whereIn('checkID', $uniqueCheckIDs)
                                  ->get();
            
            // If no verifications found or count doesn't match checkpoints, delivery is not complete
            if ($verifications->isEmpty() || count($verifications) < count($uniqueCheckIDs)) {
                return false;
            }
            
            // Check if all verifications are complete
            foreach ($verifications as $verify) {
                if ($verify->verify_status !== 'complete') {
                    return false; // At least one verification is not complete
                }
            }
            
            // All verifications are complete, update delivery end_timestamp
            $delivery = \App\Models\Delivery::find($deliveryID);
            if ($delivery) {
                $delivery->end_timestamp = Carbon::now();
                $delivery->save();
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Error checking delivery completion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}