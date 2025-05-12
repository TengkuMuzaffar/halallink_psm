<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Verify;
use App\Models\Checkpoint;
use App\Models\Delivery;
use Illuminate\Support\Facades\Validator;

class QRcodeController extends Controller
{
    /**
     * Process QR code scan and create verifications
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processQRCode(Request $request)
    {
        try {
            // Log the request data
            Log::info('QR code scan request received', [
                'request_data' => $request->all()
            ]);
            
            // Validate request
            $validator = Validator::make($request->all(), [
                'orderID' => 'required|exists:orders,orderID',
                'locationID' => 'required|exists:locations,locationID',
                'checkpoints' => 'nullable|array',
                'checkpoints.*' => 'exists:checkpoints,checkID',
                'deliveryID' => 'nullable|exists:deliveries,deliveryID',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $orderID = $request->orderID;
            $locationID = $request->locationID;
            
            // If no checkpoints or deliveryID provided, just return the order and location info
            if (!$request->has('checkpoints') || !$request->has('deliveryID')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'QR code scanned successfully',
                    'data' => [
                        'orderID' => $orderID,
                        'locationID' => $locationID
                    ]
                ], 200);
            }
            
            $checkpoints = $request->checkpoints;
            $deliveryID = $request->deliveryID;
            
            // Verify that all checkpoints belong to the specified order and location
            $validCheckpoints = Checkpoint::whereIn('checkID', $checkpoints)
                ->where('orderID', $orderID)
                ->where('locationID', $locationID)
                ->get();
            
            if (count($validCheckpoints) !== count($checkpoints)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'One or more checkpoints are invalid for this order and location'
                ], 400);
            }
            
            // Get the delivery to verify it exists
            $delivery = Delivery::find($deliveryID);
            if (!$delivery) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Delivery not found'
                ], 404);
            }
            
            // Create verify records for each checkpoint
            $verifyRecords = [];
            foreach ($checkpoints as $checkID) {
                $verify = new Verify();
                $verify->checkID = $checkID;
                $verify->deliveryID = $deliveryID;
                $verify->verify_status = 'complete';
                $verify->timestamp = now();
                $verify->save();
                
                $verifyRecords[] = $verify;
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'QR code processed successfully',
                'data' => [
                    'verifications' => $verifyRecords,
                    'count' => count($verifyRecords)
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error processing QR code', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while processing the QR code',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}