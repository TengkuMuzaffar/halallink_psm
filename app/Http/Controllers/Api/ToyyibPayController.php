<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Checkpoint;
use App\Models\Task;
use App\Models\Trip;
use App\Models\User;
use App\Models\Item;
use App\Models\Location;
use App\Models\SortLocation;
use App\Mail\OrderPaidNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ToyyibPayController extends Controller
{
    public function createBill(Request $request)
    {
        try {
            
            // Get authenticated user
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Validate delivery location
            $locationID = $request->input('locationID');
            
            if (!$locationID) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select a delivery location'
                ], 400);
            }
            
            // Get draft order
            $order = Order::where('userID', $user->userID)
                          ->where('order_status', 'draft')
                          ->first();
                          
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'No items in cart'
                ], 400);
            }
            
            // Update order with delivery location
            $order->locationID = $locationID;
            $order->save();
            
            // Get cart items
            $cartItems = Cart::where('orderID', $order->orderID)
                             ->with(['item.poultry', 'item.user.company', 'item.location'])
                             ->get();
                             
            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No items in cart'
                ], 400);
            }
            
            // Check for soft-deleted items or locations
            $unavailableItems = [];
            foreach ($cartItems as $cartItem) {
                // Use withTrashed to be able to check trashed status
                $item = Item::withTrashed()->find($cartItem->itemID);
                $location = $item ? Location::withTrashed()->find($item->locationID) : null;
                
                if (!$item || $item->trashed() || !$location || $location->trashed()) {
                    $itemName = $item && $item->poultry ? $item->poultry->poultry_name : 'Unknown item';
                    $unavailableItems[] = $itemName;
                }
            }
            
            if (!empty($unavailableItems)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some items in your cart are no longer available',
                    'error' => 'Please remove the following items from your cart: ' . implode(', ', $unavailableItems),
                    'unavailable_items' => $unavailableItems
                ], 400);
            }
            
            // Calculate total amount
            $totalAmount = 0;
            foreach ($cartItems as $item) {
                $totalAmount += $item->price_at_purchase * $item->quantity;
                \Log::info('Item in cart', [
                    'itemID' => $item->itemID,
                    'price' => $item->price_at_purchase,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->price_at_purchase * $item->quantity
                ]);
            }
            
            // Format amount for ToyyibPay (in cents)
            $amountInCents = $totalAmount * 100;
            // Flag to determine if we need to create a new bill
            $createNewBill = true;
            $billCode = null;
            $payment = null;
            
            if ($order->paymentID) {
                // Use existing payment
                $payment = Payment::find($order->paymentID);
                
                // Check if payment has a valid bill_code and status is pending
                if ($payment->bill_code && $payment->payment_status === 'pending') {
                    $billCode = $payment->bill_code;
                    $createNewBill = false;
                    
                    // Update payment amount if needed
                    if ($payment->payment_amount != $totalAmount) {
                        $payment->payment_amount = $totalAmount;
                        $payment->save();
                    }
                } else {
                    // Generate a new reference number
                    $referenceNo = 'HL-' . strtoupper(Str::random(8));
                    
                    // Update payment details
                    $payment->payment_amount = $totalAmount;
                    $payment->payment_status = 'pending';
                    $payment->payment_timestamp = now();
                    $payment->payment_reference = $referenceNo;
                    $payment->save();
                }
            } else {
                // Generate a new reference number
                $referenceNo = 'HL-' . strtoupper(Str::random(8));
                \Log::info('Generated reference number', ['referenceNo' => $referenceNo]);
                
                // Create payment record
                \Log::info('Creating new payment record');
                $payment = Payment::create([
                    'payment_amount' => $totalAmount,
                    'payment_status' => 'pending',
                    'payment_timestamp' => now(),
                    'payment_reference' => $referenceNo,
                ]);
                
                // Update order with payment ID
                $order->paymentID = $payment->paymentID;
                $order->save();
            }
            
            // If we have a valid bill code, return it without creating a new one
            if (!$createNewBill && $billCode) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment initialized (reusing existing bill)',
                    'redirect_url' => "https://dev.toyyibpay.com/{$billCode}"
                ]);
            }
            
            // If we need to create a new bill, proceed with the normal flow
            if ($user->role === 'admin') {
                // For admin users, use company name
                $company = $user->company;
                \Log::info('Admin user company check', ['hasCompany' => (bool)$company]);
                $billToName = $company ? trim($company->company_name) : '';
                
                // If company name is empty, fallback to user's fullname
                if (empty($billToName)) {
                    $billToName = trim($user->fullname);
                }
            } else {
                // For employees, use fullname
                $billToName = trim($user->fullname);
            }
            
            // If still empty, use a default name
            if (empty($billToName)) {
                $billToName = 'HalalLink Customer';
            }
            
            // Ensure we have a valid email
            $billEmail = $user->email;
            if (empty($billEmail)) {
                $billEmail = 'customer@example.com';
            }
            
            // Ensure we have a valid phone number
            $billPhone = $user->tel_number;
            if (empty($billPhone)) {
                $billPhone = '0123456789';
            }           
            
            // Prepare bill data for ToyyibPay
            $billData = [
                'userSecretKey' => config('toyyibpay.key'),
                'categoryCode' => config('toyyibpay.category'),
                'billName' => 'HalalLink Order #' . $order->orderID,
                'billDescription' => 'Payment for order #' . $order->orderID,
                'billPriceSetting' => 1,
                'billPayorInfo' => 1,
                'billAmount' => $amountInCents,
                'billReturnUrl' => route('payment.status'),
                // 'billCallbackUrl' => route('payment.callback'),
                'billExternalReferenceNo' => $payment->payment_reference,
                'billTo' => $billToName,
                'billEmail' => $billEmail,
                'billPhone' => $billPhone,
                'billSplitPayment' => 0,
                'billSplitPaymentArgs' => '',
                'billPaymentChannel' => 0,
                'billDisplayMerchant' => 1,
                'billContentEmail' => 'Thank you for your order with HalalLink!',
                'billChargeToCustomer' => 2
            ];
            // Call ToyyibPay API
            $url = 'https://dev.toyyibpay.com/index.php/api/createBill';
            $response = Http::asForm()->post($url, $billData);
            
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to payment gateway: ' . $response->status()
                ], 500);
            }
            
            $responseData = $response->json();
            
            // Check if response is valid
            if (empty($responseData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Empty response from payment gateway'
                ], 500);
            }
            
            // Check if response has the expected format
            if (!isset($responseData[0]) || !is_array($responseData[0])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unexpected response format from payment gateway'
                ], 500);
            }
            
            // Check if BillCode exists in the response
            if (!isset($responseData[0]['BillCode'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing BillCode in payment gateway response',
                    'response_data' => $responseData
                ], 500);
            }
            
            $billCode = $responseData[0]['BillCode'];
            
            // Save bill code to payment record
            $payment->bill_code = $billCode;
            $payment->save();
            
            // Return success with redirect URL
            return response()->json([
                'success' => true,
                'message' => 'Payment initialized',
                'redirect_url' => "https://dev.toyyibpay.com/{$billCode}"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send order notification emails to relevant companies
     * 
     * @param Order $order The order that was paid
     * @param array $cartItems The cart items in the order
     * @return void
     */
    private function sendOrderNotifications(Order $order, $cartItems)
    {
        try {
            // Get the buyer information
            $buyer = User::find($order->userID);
            if (!$buyer) {
                \Log::warning('Buyer not found for order notification', ['orderID' => $order->orderID]);
                return;
            }

            // Group items by company
            $companiesItems = [];
            
            foreach ($cartItems as $cartItem) {
                if (!$cartItem->item || !$cartItem->item->user || !$cartItem->item->user->company) {
                    continue;
                }
                
                $company = $cartItem->item->user->company;
                $companyID = $company->companyID;
                
                if (!isset($companiesItems[$companyID])) {
                    $companiesItems[$companyID] = [
                        'company_id' => $companyID,
                        'company_name' => $company->company_name,
                        'items' => [],
                        'employees' => []
                    ];
                    
                    // Get all employees of this company
                    $employees = User::where('companyID', $companyID)->get();
                    foreach ($employees as $employee) {
                        $companiesItems[$companyID]['employees'][] = [
                            'email' => $employee->email,
                            'name' => $employee->fullname
                        ];
                    }
                }
                
                // Add item details
                $companiesItems[$companyID]['items'][] = [
                    'item_id' => $cartItem->item->itemID,
                    'item_name' => $cartItem->item->poultry ? $cartItem->item->poultry->poultry_name : 'Item #' . $cartItem->item->itemID,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price_at_purchase
                ];
            }
            
            // Send notification to each company
            foreach ($companiesItems as $companyData) {
                if (empty($companyData['employees']) || empty($companyData['items'])) {
                    continue;
                }
                
                // Primary recipient (first employee or admin)
                $primaryRecipient = $companyData['employees'][0]['email'];
                
                // CC recipients (other employees)
                $ccRecipients = [];
                for ($i = 1; $i < count($companyData['employees']); $i++) {
                    $ccRecipients[] = $companyData['employees'][$i]['email'];
                }
                
                // Create and send the email
                $mail = new OrderPaidNotification($order, $cartItems, $buyer, $companyData);
                
                Mail::to($primaryRecipient)
                    ->cc($ccRecipients)
                    ->send($mail);
                    
                \Log::info('Order notification sent to company', [
                    'companyID' => $companyData['company_id'],
                    'orderID' => $order->orderID,
                    'primaryRecipient' => $primaryRecipient,
                    'ccCount' => count($ccRecipients)
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error sending order notifications: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function paymentStatus(Request $request)
    {
        try {
            \Log::info('Payment status callback received', $request->all());
            \Log::info('Payment status URL: ' . url()->full());
            \Log::info('Payment status headers: ' . json_encode($request->headers->all()));
            
            // Get status parameters from ToyyibPay
            $billCode = $request->billcode;
            $paymentStatus = $request->status_id;
            $referenceNo = $request->order_id;
            $transactionId = $request->transaction_id;
            $message = $request->msg;
            
            \Log::info('Payment status parameters', [
                'billCode' => $billCode,
                'paymentStatus' => $paymentStatus,
                'referenceNo' => $referenceNo,
                'transactionId' => $transactionId,
                'message' => $message
            ]);
            
            // Find payment by reference number or bill code
            $payment = Payment::where('bill_code', $billCode)
                             ->orWhere('payment_reference', $referenceNo)
                             ->first();
            
            \Log::info('Payment lookup result', ['found' => (bool)$payment, 'paymentDetails' => $payment ? $payment->toArray() : null]);
                             
            if (!$payment) {
                \Log::warning('Payment not found', ['billCode' => $billCode, 'referenceNo' => $referenceNo]);
                
                // Redirect to frontend payment status page with error params
                $redirectUrl = '/payment-status?success=false&message=' . urlencode('Payment not found');
                \Log::info('Redirecting to: ' . $redirectUrl);
                return redirect($redirectUrl);
            }
            
            // Get order
            $order = Order::where('paymentID', $payment->paymentID)->first();
            \Log::info('Order lookup result', ['found' => (bool)$order, 'orderDetails' => $order ? $order->toArray() : null]);
            
            if (!$order) {
                \Log::warning('Order not found', ['paymentID' => $payment->paymentID]);
                
                // Redirect to frontend with error
                $redirectUrl = '/payment-status?success=false&message=' . urlencode('Order not found');
                \Log::info('Redirecting to: ' . $redirectUrl);
                return redirect($redirectUrl);
            }
            
            // Update payment status based on ToyyibPay status
            $originalStatus = $payment->payment_status;
            switch ($paymentStatus) {
                // Similarly in callback method:
                case '1': // Success
                    $payment->payment_status = 'completed';
                    $payment->transaction_id = $transactionId;
                    $order->markAsPaid(); 
                    
                    // Get cart items with necessary relationships
                    $cartItems = Cart::where('orderID', $order->orderID)
                                ->with([
                                    'item.user.company',
                                    'item.location.company',
                                    'item.slaughterhouse.company'
                                ])
                                ->get();
                    
                    foreach ($cartItems as $cartItem) {
                        if ($cartItem->item) {
                            $cartItem->item->decreaseStock($cartItem->quantity);
                        }
                    }
                    
                    // Send notifications to companies
                    $this->sendOrderNotifications($order, $cartItems);
                    
                    // Create checkpoints
                    $this->createCheckpoints($order);
                    break;
                case '2': // Pending
                    $payment->payment_status = 'pending';
                    \Log::info('Payment pending', ['orderID' => $order->orderID]);
                    break;
                case '3': // Failed
                    $payment->payment_status = 'failed';
                    \Log::warning('Payment failed', ['orderID' => $order->orderID]);
                    break;
                default:
                    $payment->payment_status = 'unknown';
                    \Log::warning('Unknown payment status', ['status' => $paymentStatus, 'orderID' => $order->orderID]);
            }
            
            \Log::info('Updating payment status', [
                'from' => $originalStatus,
                'to' => $payment->payment_status,
                'paymentID' => $payment->paymentID
            ]);
            
            $payment->save();
            $order->save();
            
            \Log::info('Payment and order updated successfully');
            
            // Format amount for display
            $formattedAmount = 'RM ' . number_format($payment->payment_amount, 2);
            
            // Redirect to frontend payment status page with all necessary parameters
            $redirectUrl = '/payment-status?' . http_build_query([
                'success' => ($payment->payment_status === 'completed') ? 'true' : 'false',
                'status' => $payment->payment_status,
                'order_id' => $order->orderID,
                'reference' => $referenceNo,
                'transaction_id' => $transactionId,
                'amount' => $formattedAmount,
                'message' => ($payment->payment_status === 'completed') ? 'Payment successful!' : 'Payment ' . $payment->payment_status
            ]);
            
            \Log::info('Redirecting to payment status page', ['url' => $redirectUrl]);
            return redirect($redirectUrl);
            
        } catch (\Exception $e) {
            \Log::error('Error in payment status callback: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Redirect to frontend with error
            $redirectUrl = '/payment-status?success=false&message=' . urlencode('An error occurred while processing your payment');
            \Log::info('Redirecting to error page: ' . $redirectUrl);
            return redirect($redirectUrl);
        }
    }

    public function callBack(Request $request)
    {
        try {
            \Log::info('ToyyibPay Callback', $request->all());
            \Log::info('Callback URL: ' . url()->full());
            \Log::info('Callback headers: ' . json_encode($request->headers->all()));
            
            // Get status parameters from ToyyibPay
            $billCode = $request->billcode;
            $paymentStatus = $request->status_id;
            $referenceNo = $request->order_id;
            $transactionId = $request->transaction_id;
            $message = $request->msg;
            
            \Log::info('Callback parameters', [
                'billCode' => $billCode,
                'paymentStatus' => $paymentStatus,
                'referenceNo' => $referenceNo,
                'transactionId' => $transactionId,
                'message' => $message
            ]);
            
            // Find payment by reference number or bill code
            $payment = Payment::where('bill_code', $billCode)
                             ->orWhere('payment_reference', $referenceNo)
                             ->first();
            
            \Log::info('Callback payment lookup', ['found' => (bool)$payment, 'paymentDetails' => $payment ? $payment->toArray() : null]);
                             
            if (!$payment) {
                \Log::warning('Callback payment not found', ['billCode' => $billCode, 'referenceNo' => $referenceNo]);
                return response()->json(['status' => 'error', 'message' => 'Payment not found']);
            }
            
            // Get order
            $order = Order::where('paymentID', $payment->paymentID)->first();
            \Log::info('Callback order lookup', ['found' => (bool)$order]);
            
            if (!$order) {
                \Log::warning('Callback order not found', ['paymentID' => $payment->paymentID]);
                return response()->json(['status' => 'error', 'message' => 'Order not found']);
            }
            
            // Update payment status based on ToyyibPay status
            $originalStatus = $payment->payment_status;
            switch ($paymentStatus) {
                // Inside the case '1': // Success section
                case '1': // Success
                    $payment->payment_status = 'completed';
                    $payment->transaction_id = $transactionId;
                    $order->markAsPaid(); 
                    
                    // Get cart items with necessary relationships
                    $cartItems = Cart::where('orderID', $order->orderID)
                                ->with([
                                    'item.user.company',
                                    'item.location.company',
                                    'item.slaughterhouse.company'
                                ])
                                ->get();
                    
                    foreach ($cartItems as $cartItem) {
                        if ($cartItem->item) {
                            $cartItem->item->decreaseStock($cartItem->quantity);
                        }
                    }
                    
                    // Create checkpoints
                    $this->createCheckpoints($order);
                    break;
                case '2': // Pending
                    $payment->payment_status = 'pending';
                    \Log::info('Callback payment pending', ['orderID' => $order->orderID]);
                    break;
                case '3': // Failed
                    $payment->payment_status = 'failed';
                    \Log::warning('Callback payment failed', ['orderID' => $order->orderID]);
                    break;
                default:
                    $payment->payment_status = 'unknown';
                    \Log::warning('Callback unknown payment status', ['status' => $paymentStatus, 'orderID' => $order->orderID]);
            }
            
            \Log::info('Callback updating payment status', [
                'from' => $originalStatus,
                'to' => $payment->payment_status,
                'paymentID' => $payment->paymentID
            ]);
            
            $payment->save();
            $order->save();
            
            \Log::info('Callback payment and order updated successfully');
            
            return response()->json(['status' => 'success']);
            
        } catch (\Exception $e) {
            \Log::error('ToyyibPay Callback Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function createCheckpoints(Order $order)
    {
        try {
            // Check if checkpoints already exist for this order
            $existingCheckpoints = Checkpoint::where('orderID', $order->orderID)->exists();

            if ($existingCheckpoints) {
                \Log::info('Checkpoints already exist for order #' . $order->orderID . '. Skipping creation.');
                return response()->json([
                    'status' => 'success',
                    'message' => 'Checkpoints already exist for this order',
                    'data' => [
                        'order_id' => $order->orderID
                    ]
                ], 200);
            }

            // Get cart items with necessary relationships
            $cartItems = Cart::where('orderID', $order->orderID)
                            ->with([
                                'item.user.company',
                                'item.location.company',
                                'item.slaughterhouse.company'
                            ])
                            ->get();
            
            // Group items by supplier company to avoid duplicates
            $itemsBySupplier = $cartItems->groupBy('item.user.company.companyID');
            
            $checkpoints = [];
            $supplierCheckpoints = []; // Track supplier checkpoints by companyID
            
            // Create checkpoints for each unique supplier
            foreach ($itemsBySupplier as $companyID => $items) {
                $firstItem = $items->first();
                
                // First checkpoint - Supplier location
                $checkpoint1 = Checkpoint::create([
                    'orderID' => $order->orderID,
                    'companyID' => $companyID,
                    'locationID' => $firstItem->item->locationID,
                    'arrange_number' => 1,
                    'item_record' => $items->filter(function($cartItem) use ($firstItem) {
                        // Only include items from this supplier location
                        return $cartItem->item->locationID === $firstItem->item->locationID;
                    })->pluck('itemID')->toArray()
                ]);
                
                $checkpoints[] = $checkpoint1;
                $supplierCheckpoints[$companyID] = $checkpoint1;
                
                // Group items by slaughterhouse location
                $itemsBySlaughterhouse = $items->groupBy('item.slaughterhouse_locationID');
                
                foreach ($itemsBySlaughterhouse as $slaughterhouseLocationID => $slaughterhouseItems) {
                    if (!$slaughterhouseLocationID || !$slaughterhouseItems->first()->item->slaughterhouse) {
                        continue;
                    }
                    
                    $slaughterhouseItem = $slaughterhouseItems->first();
                    $slaughterhouseCompanyID = $slaughterhouseItem->item->slaughterhouse->company->companyID;
                    
                    // Second checkpoint - Slaughterhouse (receiving from supplier)
                    $checkpoint2 = Checkpoint::create([
                        'orderID' => $order->orderID,
                        'companyID' => $slaughterhouseCompanyID,
                        'locationID' => $slaughterhouseLocationID,
                        'arrange_number' => 2,
                        'item_record' => $slaughterhouseItems->pluck('itemID')->toArray()
                    ]);
                    
                    $task = Task::create([
                        'checkID' => $checkpoint2->checkID,
                        'task_type' => 'slaughter',
                        'task_status' => 'pending',
                        'start_timestamp' => null,
                        'finish_timestamp' => null
                    ]);
                    
                    $checkpoints[] = $checkpoint2;
                    
                    // Create Trip for arrange_number 1 to 2
                    Trip::create([
                        'deliveryID' => null,
                        'start_checkID' => $checkpoint1->checkID,
                        'end_checkID' => $checkpoint2->checkID,
                        'orderID' => $order->orderID
                    ]);
                    
                    // Third checkpoint - Slaughterhouse (sending to customer)
                    $checkpoint3 = Checkpoint::create([
                        'orderID' => $order->orderID,
                        'companyID' => $slaughterhouseCompanyID,
                        'locationID' => $slaughterhouseLocationID,
                        'arrange_number' => 3,
                        'item_record' => $slaughterhouseItems->pluck('itemID')->toArray()
                    ]);
                    
                    $checkpoints[] = $checkpoint3;
                }
            }
            
            // Fourth checkpoint - Customer's company (includes all items)
            $checkpoint4 = Checkpoint::create([
                'orderID' => $order->orderID,
                'companyID' => $order->user->company->companyID,
                'locationID' => $order->locationID,
                'arrange_number' => 4,
                'item_record' => $cartItems->pluck('itemID')->toArray() // All items for the order
            ]);
            
            $checkpoints[] = $checkpoint4;
            
            // Create Trips for arrange_number 3 to 4 (Slaughterhouse to Customer)
            // Get all checkpoints with arrange_number 3 for this order
            $checkpoints3 = Checkpoint::where('orderID', $order->orderID)
                ->where('arrange_number', 3)
                ->get();
                
            foreach ($checkpoints3 as $checkpoint3) {
                Trip::create([
                    'deliveryID' => null, // Will be assigned when delivery is created
                    'start_checkID' => $checkpoint3->checkID,
                    'end_checkID' => $checkpoint4->checkID,
                    'orderID' => $order->orderID // Add the orderID here
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Checkpoints created successfully',
                'data' => [
                    'order_id' => $order->orderID,
                    'checkpoints' => $checkpoints
                ]
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Error creating checkpoints: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create checkpoints: ' . $e->getMessage(),
            ], 500);
        }
    }

}