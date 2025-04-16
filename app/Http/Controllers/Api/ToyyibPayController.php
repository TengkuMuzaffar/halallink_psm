<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Checkpoint;
use App\Models\Task;
use App\Models\SortLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ToyyibPayController extends Controller
{
    public function createBill(Request $request)
    {
        try {
            \Log::info('Starting payment process');
            
            // Get authenticated user
            $user = Auth::user();
            \Log::info('User authentication check', ['authenticated' => (bool)$user]);
            
            if (!$user) {
                \Log::warning('Payment failed: User not authenticated');
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            // Validate delivery location
            $locationID = $request->input('locationID');
            \Log::info('Checking delivery location', ['locationID' => $locationID]);
            
            if (!$locationID) {
                \Log::warning('Payment failed: No delivery location selected');
                return response()->json([
                    'success' => false,
                    'message' => 'Please select a delivery location'
                ], 400);
            }
            
            // Get draft order
            \Log::info('Fetching draft order for user', ['userID' => $user->userID]);
            $order = Order::where('userID', $user->userID)
                          ->where('order_status', 'draft')
                          ->first();
            \Log::info('Draft order check', ['orderFound' => (bool)$order]);
                          
            if (!$order) {
                \Log::warning('Payment failed: No draft order found');
                return response()->json([
                    'success' => false,
                    'message' => 'No items in cart'
                ], 400);
            }
            
            // Update order with delivery location
            $order->locationID = $locationID;
            $order->save();
            \Log::info('Updated order with delivery location', ['orderID' => $order->orderID, 'locationID' => $locationID]);
            
            // Get cart items
            \Log::info('Fetching cart items for order', ['orderID' => $order->orderID]);
            $cartItems = Cart::where('orderID', $order->orderID)
                             ->with(['item.poultry', 'item.user.company', 'item.location'])
                             ->get();
            \Log::info('Cart items check', ['itemCount' => $cartItems->count()]);
                             
            if ($cartItems->isEmpty()) {
                \Log::warning('Payment failed: No cart items found');
                return response()->json([
                    'success' => false,
                    'message' => 'No items in cart'
                ], 400);
            }
            
            // Calculate total amount
            \Log::info('Calculating total amount');
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
            \Log::info('Total amount calculated', ['totalAmount' => $totalAmount]);
            
            // Format amount for ToyyibPay (in cents)
            $amountInCents = $totalAmount * 100;
            \Log::info('Amount in cents', ['amountInCents' => $amountInCents]);
            
            // Generate a unique reference number
            $referenceNo = 'HL-' . strtoupper(Str::random(8));
            \Log::info('Generated reference number', ['referenceNo' => $referenceNo]);
            
            // Check if order already has a payment
            \Log::info('Checking if order already has a payment ID', ['currentPaymentID' => $order->paymentID]);
            if ($order->paymentID) {
                // Use existing payment
                $payment = Payment::find($order->paymentID);
                \Log::info('Using existing payment', ['paymentID' => $payment->paymentID]);
                
                // Update payment details
                $payment->payment_amount = $totalAmount;
                $payment->payment_status = 'pending';
                $payment->payment_timestamp = now();
                $payment->payment_reference = $referenceNo;
                $payment->save();
                \Log::info('Updated existing payment record', ['paymentID' => $payment->paymentID]);
            } else {
                // Create payment record
                \Log::info('Creating new payment record');
                $payment = Payment::create([
                    'payment_amount' => $totalAmount,
                    'payment_status' => 'pending',
                    'payment_timestamp' => now(),
                    'payment_reference' => $referenceNo,
                ]);
                \Log::info('Payment record created', ['paymentID' => $payment->paymentID]);
                
                // Update order with payment ID
                \Log::info('Updating order with payment ID');
                $order->paymentID = $payment->paymentID;
                $order->save();
            }
            \Log::info('Order payment ID confirmed', ['orderID' => $order->orderID, 'paymentID' => $payment->paymentID]);
            
            // Make sure we have a valid name for billTo parameter
            \Log::info('Determining billTo name based on user role', ['role' => $user->role]);
            if ($user->role === 'admin') {
                // For admin users, use company name
                $company = $user->company;
                \Log::info('Admin user company check', ['hasCompany' => (bool)$company]);
                $billToName = $company ? trim($company->company_name) : '';
                \Log::info('Using company name for admin', ['companyName' => $billToName]);
                
                // If company name is empty, fallback to user's fullname
                if (empty($billToName)) {
                    $billToName = trim($user->fullname);
                    \Log::info('Company name empty, falling back to user fullname', ['fullname' => $billToName]);
                }
            } else {
                // For employees, use fullname
                $billToName = trim($user->fullname);
                \Log::info('Using fullname for non-admin user', ['fullname' => $billToName]);
            }
            
            // If still empty, use a default name
            if (empty($billToName)) {
                $billToName = 'HalalLink Customer';
                \Log::info('Name still empty, using default name', ['defaultName' => $billToName]);
            }
            
            // Ensure we have a valid email
            $billEmail = $user->email;
            if (empty($billEmail)) {
                $billEmail = 'customer@example.com';
                \Log::info('Email empty, using default email', ['defaultEmail' => $billEmail]);
            }
            
            // Ensure we have a valid phone number
            $billPhone = $user->tel_number;
            if (empty($billPhone)) {
                $billPhone = '0123456789';
                \Log::info('Phone empty, using default phone', ['defaultPhone' => $billPhone]);
            }
            
            // Log the user data for debugging
            \Log::info('User data for payment', [
                'userID' => $user->userID,
                'role' => $user->role,
                'name' => $billToName,
                'email' => $billEmail,
                'phone' => $billPhone
            ]);
            
            // Prepare bill data for ToyyibPay
            \Log::info('Preparing ToyyibPay bill data');
            $billData = [
                'userSecretKey' => config('toyyibpay.key'),
                'categoryCode' => config('toyyibpay.category'),
                'billName' => 'HalalLink Order #' . $order->orderID,
                'billDescription' => 'Payment for order #' . $order->orderID,
                'billPriceSetting' => 1,
                'billPayorInfo' => 1,
                'billAmount' => $amountInCents,
                'billReturnUrl' => route('payment.status'),
                'billCallbackUrl' => route('payment.callback'),
                'billExternalReferenceNo' => $referenceNo,
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
            
            // Log the request data for debugging
            \Log::info('ToyyibPay API Request', $billData);
            
            // Call ToyyibPay API
            \Log::info('Calling ToyyibPay API');
            $url = 'https://dev.toyyibpay.com/index.php/api/createBill';
            $response = Http::asForm()->post($url, $billData);
            
            // Log the response for debugging
            \Log::info('ToyyibPay API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json()
            ]);
            
            if (!$response->successful()) {
                \Log::error('ToyyibPay API request failed', [
                    'status' => $response->status(),
                    'reason' => $response->reason()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to payment gateway: ' . $response->status()
                ], 500);
            }
            
            $responseData = $response->json();
            
            // Check if response is valid
            if (empty($responseData)) {
                \Log::error('Empty response from payment gateway');
                return response()->json([
                    'success' => false,
                    'message' => 'Empty response from payment gateway'
                ], 500);
            }
            
            // Check if response has the expected format
            if (!isset($responseData[0]) || !is_array($responseData[0])) {
                \Log::error('Unexpected response format from payment gateway', ['response' => $responseData]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unexpected response format from payment gateway'
                ], 500);
            }
            
            // Check if BillCode exists in the response
            if (!isset($responseData[0]['BillCode'])) {
                \Log::error('Missing BillCode in payment gateway response', ['response' => $responseData]);
                return response()->json([
                    'success' => false,
                    'message' => 'Missing BillCode in payment gateway response',
                    'response_data' => $responseData
                ], 500);
            }
            
            $billCode = $responseData[0]['BillCode'];
            \Log::info('Successfully received BillCode', ['billCode' => $billCode]);
            
            // Save bill code to payment record
            \Log::info('Saving bill code to payment record');
            $payment->bill_code = $billCode;
            $payment->save();
            \Log::info('Bill code saved to payment record');
            
            // Return success with redirect URL
            \Log::info('Payment process completed successfully', [
                'billCode' => $billCode,
                'redirectUrl' => "https://dev.toyyibpay.com/{$billCode}"
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Payment initialized',
                'redirect_url' => "https://dev.toyyibpay.com/{$billCode}"
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Payment creation error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
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
                    $order->order_status = 'paid';
                    
                    // Update stock for each item
                    $cartItems = Cart::where('orderID', $order->orderID)->with(['item'])->get();
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
                    $order->order_status = 'paid';
                    
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
            
            // Create checkpoints for each unique supplier
            foreach ($itemsBySupplier as $companyID => $items) {
                $firstItem = $items->first();
                
                // First checkpoint - Supplier location
                $checkpoint1 = Checkpoint::create([
                    'orderID' => $order->orderID,
                    'companyID' => $companyID,
                    'locationID' => $firstItem->item->locationID,
                    'arrange_number' => 1,
                    'arrive_timestamp' => null
                ]);
                
                $sortLocation1 = SortLocation::create([
                    'checkID' => $checkpoint1->checkID,
                    'deliveryID' => null
                ]);
                
                $checkpoints[] = [
                    'checkpoint' => $checkpoint1,
                    'sort_location' => $sortLocation1
                ];
                
                // Second checkpoint - Slaughterhouse
                if ($firstItem->item->slaughterhouse) {
                    $checkpoint2 = Checkpoint::create([
                        'orderID' => $order->orderID,
                        'companyID' => $firstItem->item->slaughterhouse->company->companyID,
                        'locationID' => $firstItem->item->slaughterhouse_locationID,
                        'arrange_number' => 2,
                        'arrive_timestamp' => null
                    ]);
                    
                    $sortLocation2 = SortLocation::create([
                        'checkID' => $checkpoint2->checkID,
                        'deliveryID' => null
                    ]);
                    
                    $task = Task::create([
                        'checkID' => $checkpoint2->checkID,
                        'task_type' => 'slaughter',
                        'task_status' => 'pending',
                        'start_timestamp' => null,
                        'finish_timestamp' => null
                    ]);
                    
                    $checkpoints[] = [
                        'checkpoint' => $checkpoint2,
                        'sort_location' => $sortLocation2,
                        'task' => $task
                    ];
                    
                    // Third checkpoint - Same as second
                    $checkpoint3 = Checkpoint::create([
                        'orderID' => $order->orderID,
                        'companyID' => $firstItem->item->slaughterhouse->company->companyID,
                        'locationID' => $firstItem->item->slaughterhouse_locationID,
                        'arrange_number' => 3,
                        'arrive_timestamp' => null
                    ]);
                    
                    $sortLocation3 = SortLocation::create([
                        'checkID' => $checkpoint3->checkID,
                        'deliveryID' => null
                    ]);
                    
                    $checkpoints[] = [
                        'checkpoint' => $checkpoint3,
                        'sort_location' => $sortLocation3
                    ];
                }
            }
            
            // Fourth checkpoint - Customer's company
            $checkpoint4 = Checkpoint::create([
                'orderID' => $order->orderID,
                'companyID' => $order->user->company->companyID,
                'locationID' => $order->locationID,
                'arrange_number' => 4,
                'arrive_timestamp' => null
            ]);
            
            $sortLocation4 = SortLocation::create([
                'checkID' => $checkpoint4->checkID,
                'deliveryID' => null
            ]);
            
            $checkpoints[] = [
                'checkpoint' => $checkpoint4,
                'sort_location' => $sortLocation4
            ];

       
            // // Dump and die with both checkpoints and tasks
            // dd([
            //     'checkpoints' => Checkpoint::where('orderID', $order->orderID)->get(),
            //     'tasks' => Task::whereIn('checkID', 
            //         Checkpoint::where('orderID', $order->orderID)->pluck('checkID')
            //     )->get()
            // ]);
        
        } catch (\Exception $e) {
            dd([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

}