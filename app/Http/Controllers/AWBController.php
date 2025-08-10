<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AWBController extends Controller
{
    public function generate(Cart $cart, Request $request)
    {
        // Load relationships including poultry and user
        $cart->load([
            'item.poultry',
            'item.location.company.admin',  // Load the admin user relationship
            'item.slaughterhouse.company.admin',
            'order.location.company.admin'
        ]);

        // Get company type from request parameters instead of Auth
        $companyType = $request->query('company_type');
        
        // Set source and destination based on company type
        if ($companyType === 'broiler') {
            // For broiler companies: source = locationID, destination = slaughterhouse_locationID
            $source = $cart->item->location;
            $destination = $cart->item->slaughterhouse;
        } else if ($companyType === 'slaughterhouse') { // Assuming 'sme' is the slaughterhouse company type
            // For slaughterhouse companies: source = slaughterhouse_locationID, destination = order's locationID
            $source = $cart->item->slaughterhouse;
            $destination = $cart->order->location;
        } else {
            // Default fallback if company type is unknown
            $source = $cart->item->location;
            $destination = $cart->item->slaughterhouse;
        }

        $data = [
            'cart' => $cart,
            'source' => $source,
            'destination' => $destination,
            'companyType' => $companyType,
            'logo_path' => public_path('images/HalalLink_v1.png')
        ];

        $pdf = PDF::loadView('pdfs.awb', $data);
        
        // Check if download parameter is set
        if ($request->has('download')) {
            return $pdf->download("awb-{$cart->cartID}.pdf");
        }
        
        // Default to stream/preview
        return $pdf->stream("awb-{$cart->cartID}.pdf");
    }
}