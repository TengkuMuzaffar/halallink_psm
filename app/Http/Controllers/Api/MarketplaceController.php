<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Poultry;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarketplaceController extends Controller
{
    public function getItems(Request $request)
    {
        try {
            $query = Item::with(['poultry', 'location', 'user.company'])
                ->whereHas('user.company', function($q) {
                    $q->where('company_type', 'broiler');
                });

            // Search by poultry name, location, or company name
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereHas('poultry', function($q) use ($search) {
                        $q->where('poultry_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('location', function($q) use ($search) {
                        $q->where('company_address', 'like', "%{$search}%")
                          ->orWhere('location_type', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user.company', function($q) use ($search) {
                        $q->where('company_name', 'like', "%{$search}%");
                    });
                });
            }

            // Filter by location if provided
            if ($request->has('location') && $request->location) {
                $query->whereHas('location', function($q) use ($request) {
                    $q->where('company_address', 'like', "%{$request->location}%");
                });
            }

            // Filter by poultry type
            if ($request->has('poultry_type') && $request->poultry_type) {
                $query->where('poultryID', $request->poultry_type);
            }

            // Sort items
            switch ($request->sort) {
                case 'price_low':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_high':
                    $query->orderBy('price', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }

            $items = $query->get();

            $formattedItems = $items->map(function($item) {
                return [
                    'id' => $item->itemID,
                    'name' => $item->poultry->poultry_name,
                    'price' => $item->price,
                    'quantity' => $item->measurement_value,
                    'unit' => $item->measurement_type,
                    'seller' => $item->user->company->company_name,
                    'location' => $item->location->company_address,
                    'location_type' => $item->location->location_type,
                    'image' => $item->item_image ? asset('storage/' . $item->item_image) : 
                              ($item->poultry->poultry_image ? asset('storage/' . $item->poultry->poultry_image) : null),
                    'created_at' => $item->created_at
                ];
            });

            return response()->json($formattedItems);
        } catch (\Exception $e) {
            Log::error('Error in marketplace items: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch items'], 500);
        }
    }

    public function getPoultryTypes()
    {
        try {
            $poultryTypes = Poultry::select('poultryID', 'poultry_name')->get();
            return response()->json($poultryTypes);
        } catch (\Exception $e) {
            Log::error('Error fetching poultry types: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch poultry types'], 500);
        }
    }
}