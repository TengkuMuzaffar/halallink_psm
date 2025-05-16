<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Verify;
use App\Models\Checkpoint;
use App\Models\Delivery;
use App\Models\Trip; // Import Trip model
use App\Models\Item; // Import Item model
use App\Models\Poultry; // Import Poultry model

class VerifyController extends Controller
{
    /**
     * Get verifications for a delivery and location
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'deliveryID' => 'required|exists:deliveries,deliveryID',
                'locationID' => 'nullable|exists:locations,locationID',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $query = Verify::where('deliveryID', $request->deliveryID);
            
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
    public function show($verifyID)
    {
        try {
            // Eager load checkpoint
            $verify = Verify::with('checkpoint')->find($verifyID);

            if (!$verify) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Verification not found'
                ], 404);
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

                    // Items are those in start_checkID's item_record not in end_checkID's item_record
                    $itemIDs = array_diff($startItemRecord, $endItemRecord);
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
                $items = Item::whereIn('itemID', $itemIDs)
                             ->with('poultry') // Eager load poultry to get poultry_name
                             ->get()
                             ->map(function ($item) {
                                 return [
                                     'itemID' => $item->itemID,
                                     'item_name' => $item->poultry ? $item->poultry->poultry_name : 'Unknown Poultry',
                                     'quantity' => 1, // Assuming quantity is 1 per item ID in the record
                                     // Add other relevant item details if needed
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
                'verify_status' => 'required|in:pending,verified,rejected',
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
            
            return response()->json([
                'status' => 'success',
                'message' => 'Verification updated successfully',
                'data' => $verify
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
}