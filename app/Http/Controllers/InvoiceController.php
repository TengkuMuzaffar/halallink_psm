<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function generate(Order $order, Request $request)
    {
        // Load relationships including cart items, items, poultry, and payment
        $order->load([
            'cartItems.item.poultry',
            'user',
            'payment',
            'location.company'
        ]);

        // Calculate totals
        $subtotal = 0;
        foreach ($order->cartItems as $cartItem) {
            $subtotal += $cartItem->price_at_purchase * $cartItem->quantity;
        }

        $data = [
            'order' => $order,
            'subtotal' => $subtotal,
            'total_quantity' => $order->cartItems->sum('quantity'),
            'logo_path' => public_path('images/HalalLink_v1.png')
        ];

        $pdf = PDF::loadView('pdfs.invoice', $data);
        
        // Check if download parameter is set
        if ($request->has('download')) {
            return $pdf->download("invoice-{$order->orderID}.pdf");
        }
        
        // Default to stream/preview
        return $pdf->stream("invoice-{$order->orderID}.pdf");
    }
}