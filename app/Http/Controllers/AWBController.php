<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AWBController extends Controller
{
    public function generate(Cart $cart)
    {
        // Load relationships including poultry and user
        $cart->load([
            'item.poultry',
            'item.location.company.admin',  // Load the admin user relationship
            'item.slaughterhouse.company',
            'order'
        ]);

        $data = [
            'cart' => $cart,
            'supplier' => $cart->item->location,
            'slaughterhouse' => $cart->item->slaughterhouse,
            'logo_path' => public_path('images/HalalLink_v1.png')
        ];

        $pdf = PDF::loadView('pdfs.awb', $data);
        return $pdf->download("awb-{$cart->cartID}.pdf");
    }
}