<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Verify payment status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment(Request $request)
    {
        try {
            Log::info('Payment verification request received', [
                'query' => $request->query(),
                'url' => url()->full(),
                'headers' => $request->headers->all()
            ]);
            
            $billCode = $request->query('billcode');
            $orderRef = $request->query('order_id');
            
            Log::info('Payment verification parameters', [
                'billCode' => $billCode,
                'orderRef' => $orderRef
            ]);
            
            if (!$billCode || !$orderRef) {
                Log::warning('Payment verification missing parameters', [
                    'billCode' => $billCode,
                    'orderRef' => $orderRef
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required parameters'
                ], 400);
            }
            
            // Find the payment by bill code or reference number
            $payment = Payment::where('bill_code', $billCode)
                             ->orWhere('payment_reference', $orderRef)
                             ->first();
            
            Log::info('Payment verification lookup result', [
                'found' => (bool)$payment,
                'paymentDetails' => $payment ? $payment->toArray() : null
            ]);
            
            if (!$payment) {
                Log::warning('Payment verification payment not found', [
                    'billCode' => $billCode,
                    'orderRef' => $orderRef
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }
            
            // Find the order
            $order = Order::where('paymentID', $payment->paymentID)->first();
            
            Log::info('Payment verification order lookup', [
                'found' => (bool)$order,
                'orderDetails' => $order ? $order->toArray() : null
            ]);
            
            if (!$order) {
                Log::warning('Payment verification order not found', [
                    'paymentID' => $payment->paymentID
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }
            
            // Update order status to paid if not already
            $originalOrderStatus = $order->order_status;
            $originalPaymentStatus = $payment->payment_status;
            
            if ($order->order_status !== 'paid') {
                $order->order_status = 'paid';
                $order->save();
                
                // Update payment status
                $payment->payment_status = 'completed';
                $payment->save();
                
                Log::info('Payment verification status updated', [
                    'orderID' => $order->orderID,
                    'orderStatus' => [
                        'from' => $originalOrderStatus,
                        'to' => 'paid'
                    ],
                    'paymentStatus' => [
                        'from' => $originalPaymentStatus,
                        'to' => 'completed'
                    ]
                ]);
            } else {
                Log::info('Payment verification no status update needed', [
                    'orderID' => $order->orderID,
                    'orderStatus' => $order->order_status,
                    'paymentStatus' => $payment->payment_status
                ]);
            }
            
            // Format amount for display
            $formattedAmount = 'RM ' . number_format($payment->payment_amount / 100, 2);
            
            Log::info('Payment verification successful', [
                'orderID' => $order->orderID,
                'paymentID' => $payment->paymentID,
                'amount' => $formattedAmount
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully',
                'amount' => $formattedAmount,
                'order_id' => $order->orderID,
                'payment_id' => $payment->paymentID
            ]);
        } catch (\Exception $e) {
            Log::error('Payment verification error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during payment verification'
            ], 500);
        }
    }
}