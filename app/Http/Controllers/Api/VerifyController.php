<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Verify;
use App\Models\Checkpoint;
use App\Models\Delivery;

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
     * Get a specific verification
     *
     * @param  int  $verifyID
     * @return \Illuminate\Http\Response
     */
    public function show($verifyID)
    {
        try {
            $verify = Verify::with('checkpoint')->find($verifyID);
            
            if (!$verify) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Verification not found'
                ], 404);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $verify
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error fetching verification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching verification',
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