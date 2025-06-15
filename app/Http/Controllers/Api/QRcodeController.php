<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Delivery;
use App\Models\Verify;
use App\Models\DeliveryLocationToken; // Import the model
use Illuminate\Support\Str; // Import Str facade
use Carbon\Carbon; // Import Carbon for time handling
use App\Models\Checkpoint;
use App\Models\Order;
use App\Models\Location;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRcodeController extends Controller
{
    /**
     * Process QR code scan and create verifications
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $locationID
     * @param  int  $companyID
     * @return \Illuminate\Http\Response
     */
    public function processQRCode(Request $request, $locationID, $companyID)
    {
        try {
            // Check for timestamp and expiration parameters
            $timestamp = $request->query('timestamp');
            $expirationTime = $request->query('expires');
            
            // Validate timestamp and expiration
            if (!$timestamp || !$expirationTime) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid QR code: Missing timestamp or expiration',
                    'redirect' => false
                ], 400);
            }
            
            // Check if QR code has expired (2 hour limit)
            $currentTime = time() * 1000; // Convert to milliseconds to match JS timestamp
            if ($currentTime > $expirationTime) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'QR code has expired. Please generate a new QR code.',
                    'redirect' => false
                ], 400);
            }
            
            // Validate the data
            $validator = Validator::make($request->all(), [
                'deliveryID' => 'required|exists:deliveries,deliveryID',
                'locationID' => 'required|exists:locations,locationID',
                'checkpoints' => 'required|array',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'redirect' => false
                ], 422);
            }
            
            // Verify that the posted locationID matches the URL locationID
            if ((int)$request->locationID !== (int)$locationID) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Location ID mismatch. The scanned location does not match the expected location.',
                    'redirect' => false
                ], 400);
            }
            
            $deliveryID = $request->deliveryID;
            
            // Extract checkpoint IDs from the complex structure
            $checkpoints = [];
            foreach ($request->checkpoints as $checkpoint) {
                if (isset($checkpoint['checkID'])) {
                    $checkpoints[] = $checkpoint['checkID'];
                }
            }
            
            // If no valid checkpoints were found, return an error
            if (empty($checkpoints)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No valid checkpoint IDs found in the request',
                    'redirect' => false
                ], 422);
            }
            
            // Get the delivery to verify it exists
            $delivery = Delivery::find($deliveryID);
            if (!$delivery) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Delivery not found',
                    'redirect' => false
                ], 404);
            }
            
            // Check if verifications already exist for these checkpoints
            $existingVerifications = Verify::where('deliveryID', $deliveryID)
                ->whereIn('checkID', $checkpoints)
                ->get();
            
            $existingCheckIDs = $existingVerifications->pluck('checkID')->toArray();
            $newCheckpoints = array_diff($checkpoints, $existingCheckIDs);
            
            // Create verify records only for checkpoints that don't have verifications yet
            $verifyRecords = [];
            foreach ($newCheckpoints as $checkID) {
                $verify = new Verify();
                $verify->checkID = $checkID;
                $verify->deliveryID = $deliveryID;
                $verify->verify_status = 'pending'; // Initial status is pending until form is filled
                $verify->save();
                
                $verifyRecords[] = $verify;
            }
            
            // Determine if we should redirect to the verification page
            $shouldRedirect = count($verifyRecords) > 0 || count($existingVerifications) > 0;
            // Assuming you have the token available here, perhaps from the request or a lookup
            // For this example, I'll assume the token is available in a variable $verificationToken
            // You will need to implement the logic to retrieve or generate this token
            // based on your application's requirements and the delivery/location pair.
            // For instance, you might look it up in the delivery_location_token table.

            // Example: Fetching the token (you need to implement the actual lookup logic)
            $deliveryLocationToken = DeliveryLocationToken::where('deliveryID', $deliveryID)
                                        ->where('locationID', $locationID)
                                        ->first();

            $verificationToken = $deliveryLocationToken ? $deliveryLocationToken->token : null;

            // If no token exists for this delivery/location pair, create one
            if (!$verificationToken) {
                // Generate a new token and set expiration (e.g., 2 hours from now)
                $newToken = $this->generateUniqueToken();
                $expiresAt = Carbon::now()->addHours(2); // Token expires in 2 hours

                try {
                    $deliveryLocationToken = DeliveryLocationToken::create([
                        'deliveryID' => $deliveryID,
                        'locationID' => $locationID,
                        'token' => $newToken,
                        'expired_at' => $expiresAt,
                    ]);
                    $verificationToken = $newToken; // Use the newly created token
                    Log::info('Created new DeliveryLocationToken', [
                        'deliveryID' => $deliveryID,
                        'locationID' => $locationID,
                        'token' => $newToken,
                        'expired_at' => $expiresAt,
                    ]);
                } catch (\Exception $e) {
                    // Log error if token creation fails
                    Log::error('Failed to create DeliveryLocationToken', [
                        'deliveryID' => $deliveryID,
                        'locationID' => $locationID,
                        'error' => $e->getMessage(),
                    ]);
                    // If token creation fails, we cannot redirect
                    $shouldRedirect = false;
                    $redirectUrl = null;
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to generate verification token.',
                        'redirect' => false
                    ], 500);
                }
            } else {
                 // Check if the existing token has expired
                 if ($deliveryLocationToken->expired_at && $deliveryLocationToken->expired_at->isPast()) {
                     // Token expired, generate a new one and update the record
                     $newToken = $this->generateUniqueToken();
                     $expiresAt = Carbon::now()->addHours(2);

                     try {
                         $deliveryLocationToken->update([
                             'token' => $newToken,
                             'expired_at' => $expiresAt,
                         ]);
                         $verificationToken = $newToken; // Use the newly updated token
                         Log::info('Updated expired DeliveryLocationToken', [
                             'deliveryID' => $deliveryID,
                             'locationID' => $locationID,
                             'token' => $newToken,
                             'expired_at' => $expiresAt,
                         ]);
                     } catch (\Exception $e) {
                         Log::error('Failed to update expired DeliveryLocationToken', [
                             'deliveryID' => $deliveryID,
                             'locationID' => $locationID,
                             'error' => $e->getMessage(),
                         ]);
                         $shouldRedirect = false;
                         $redirectUrl = null;
                         return response()->json([
                             'status' => 'error',
                             'message' => 'Failed to update expired verification token.',
                             'redirect' => false
                         ], 500);
                     }
                 }
                 // If token exists and is not expired, $verificationToken is already set
            }


            // Only redirect if a token is found (either existing valid or newly created) and redirect is needed
            if ($shouldRedirect && $verificationToken) {
                 $redirectUrl = "/verify?locationID={$locationID}&deliveryID={$deliveryID}&token={$verificationToken}";
            } else {
                 $redirectUrl = null; // Or a default error/info page
                 $shouldRedirect = false; // Don't redirect if no token
            }


            return response()->json([
                'status' => 'success',
                'message' => count($verifyRecords) > 0
                    ? 'QR code processed successfully. Created ' . count($verifyRecords) . ' new verification records.'
                    : 'All checkpoints already have verification records.',
                'data' => [
                    'verifications' => $verifyRecords,
                    'existingVerifications' => $existingVerifications,
                    'count' => count($verifyRecords)
                ],
                'redirect' => $shouldRedirect,
                'redirectUrl' => $redirectUrl
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error processing QR code', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while processing the QR code',
                'error' => $e->getMessage(),
                'redirect' => false
            ], 500);
        }
    }

    /**
     * Generate a unique token for delivery location verification.
     *
     * @return string
     */
    private function generateUniqueToken()
    {
        do {
            $token = Str::random(60); // Generate a random string of 60 characters
        } while (DeliveryLocationToken::where('token', $token)->exists()); // Ensure token is unique

        return $token;
    }

    /**
     * Generate QR code for location
     *
     * @param  int  $locationID
     * @param  int  $companyID
     * @return \Illuminate\Http\Response
     */
    public function generateLocationQR($locationID, $companyID)
    {
        try {
            // Check if location exists
            $location = Location::where('locationID', $locationID)
                ->where('companyID', $companyID)
                ->first();
                
            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location not found'
                ], 404);
            }
            
            // Generate URL for QR code
            $url = url("/api/qrcode/process/{$locationID}/{$companyID}");
            
            // Generate QR code
            $qrCode = QrCode::format('png')
                ->size(300)
                ->errorCorrection('H')
                ->generate($url);
                
            // Convert to base64 for frontend display
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCode);
            
            return response()->json([
                'success' => true,
                'qr_code_url' => $qrCodeBase64,
                'target_url' => $url
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating location QR code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate QR code for order verification
     *
     * @param  int  $orderID
     * @return \Illuminate\Http\Response
     */
    public function generateOrderVerifyQR($orderID)
    {
        try {
            // Check if order exists
            $order = Order::find($orderID);
            
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }
            
            // Generate URL for QR code
            $url = url("/api/qrcode/verifies/{$orderID}");
            
            // Generate QR code
            $qrCode = QrCode::format('png')
                ->size(300)
                ->errorCorrection('H')
                ->generate($url);
                
            // Convert to base64 for frontend display
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCode);
            
            return response()->json([
                'success' => true,
                'qr_code_url' => $qrCodeBase64,
                'target_url' => $url
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating order verification QR code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get verification status for an order
     *
     * @param  int  $orderID
     * @return \Illuminate\Http\Response
     */
    public function getOrderVerificationStatus($orderID)
    {
        try {
            // Get order with checkpoints and verifications
            $order = Order::with(['checkpoints.verifies'])
                ->find($orderID);
                
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }
            
            // Prepare verification status data
            $checkpointStatuses = [];
            $allVerified = true;
            
            foreach ($order->checkpoints as $checkpoint) {
                $isVerified = false;
                $verifyComment = null;
                $verifyStatus = 'pending';
                
                // Check if checkpoint has been verified
                if ($checkpoint->verifies && $checkpoint->verifies->count() > 0) {
                    $latestVerify = $checkpoint->verifies->sortByDesc('created_at')->first();
                    $isVerified = $latestVerify->isVerified();
                    $verifyComment = $latestVerify->verify_comment;
                    $verifyStatus = $latestVerify->verify_status;
                }
                
                if (!$isVerified) {
                    $allVerified = false;
                }
                
                // Get items for this checkpoint
                $items = [];
                if ($checkpoint->item_record) {
                    foreach (json_decode($checkpoint->item_record, true) as $item) {
                        $items[] = [
                            'item_name' => $item['item_name'] ?? 'Unknown Item',
                            'quantity' => $item['quantity'] ?? 0,
                            'measurement_value' => $item['measurement_value'] ?? 0,
                            'measurement_type' => $item['measurement_type'] ?? '',
                        ];
                    }
                }
                
                $checkpointStatuses[] = [
                    'checkID' => $checkpoint->checkID,
                    'arrange_number' => $checkpoint->arrange_number,
                    'is_verified' => $isVerified,
                    'verify_status' => $verifyStatus,
                    'verify_comment' => $verifyComment,
                    'items' => $items
                ];
            }
            
            return response()->json([
                'success' => true,
                'order' => [
                    'orderID' => $order->orderID,
                    'order_status' => $order->order_status,
                    'created_at' => $order->created_at,
                    'all_verified' => $allVerified
                ],
                'checkpoints' => $checkpointStatuses
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting order verification status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get verification status: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Generate QR code for SME report with embedded logo and custom colors
     *
     * @param  int  $reportValidityID
     * @return \Illuminate\Http\Response
     */
    public function generateSmeReportQR($reportValidityID)
    {
        try {
            // Check if report validity exists
            $reportValidity = \App\Models\ReportValidity::find($reportValidityID);
            
            if (!$reportValidity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Report not found'
                ], 404);
            }
            
            // Generate URL for QR code - pointing to the report PDF
            $url = url("/report-pdf/{$reportValidityID}");
            
            // Get logo path for embedding in QR code center
            $logoPath = public_path('images/HalalLink_v1.png');
            
            // Set colors from theme
            $primaryColor = '#123524'; // Dark green
            $secondaryColor = '#EFE3C2'; // Light beige
            
            // Generate QR code with embedded logo and custom colors
            $qrCode = QrCode::format('png')
                ->size(300)
                ->errorCorrection('H') // High error correction for logo embedding
                ->color(hexdec(substr($primaryColor, 1, 2)), 
                    hexdec(substr($primaryColor, 3, 2)), 
                    hexdec(substr($primaryColor, 5, 2)))
                ->backgroundColor(hexdec(substr($secondaryColor, 1, 2)), 
                                hexdec(substr($secondaryColor, 3, 2)), 
                                hexdec(substr($secondaryColor, 5, 2)))
                ->merge($logoPath, 0.3, true) // Embed logo at 30% size
                ->generate($url);
            
            // Return as downloadable PNG
            return response($qrCode, 200)
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', "attachment; filename=sme-report-qr-{$reportValidityID}.png");
        } catch (\Exception $e) {
            Log::error('Error generating SME report QR code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code: ' . $e->getMessage()
            ], 500);
        }
    }
}