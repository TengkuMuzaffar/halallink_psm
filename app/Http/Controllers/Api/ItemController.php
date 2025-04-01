<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Poultry;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ItemController extends Controller
{
    /**
     * Check if the authenticated user belongs to a broiler company
     *
     * @return bool
     */
    private function isBroilerCompany()
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        if ($user->role === 'admin') {
            $company = Company::find($user->companyID);
            return $company && $company->company_type === 'broiler';
        } elseif ($user->role === 'employee') {
            // Using the properly imported User model
            $admin = User::where('companyID', $user->companyID)
                ->where('role', 'admin')
                ->first();
                
            if ($admin) {
                $company = Company::find($admin->companyID);
                return $company && $company->company_type === 'broiler';
            }
        }
        
        return false;
    }
    
    /**
     * Display a listing of the items.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            // Get items for the user or their company (if admin)
            $query = Item::with(['poultry', 'location', 'user']);
            
            if ($user->role === 'admin') {
                // If admin, get all items from the company
                $query->whereHas('user', function($q) use ($user) {
                    $q->where('companyID', $user->companyID);
                });
            } else {
                // If employee, only get their own items
                $query->where('userID', $user->userID);
            }
            
            $items = $query->get();
            
            // Format the response
            $formattedItems = $items->map(function($item) {
                return [
                    'itemID' => $item->itemID,
                    'poultryID' => $item->poultryID,
                    'poultry_name' => $item->poultry ? $item->poultry->poultry_name : null,
                    'poultry_image' => $item->poultry && $item->poultry->poultry_image ? asset('storage/' . $item->poultry->poultry_image) : null,
                    'userID' => $item->userID,
                    'locationID' => $item->locationID,
                    'location_name' => $item->location ? $item->location->company_address : null,
                    'measurement_type' => $item->measurement_type,
                    'measurement_value' => $item->measurement_value,
                    'price' => $item->price,
                    'item_image' => $item->item_image ? asset('storage/' . $item->item_image) : null,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at
                ];
            });
            
            return response()->json($formattedItems);
        } catch (\Exception $e) {
            Log::error('Error fetching items: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Failed to fetch items', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Store a newly created item in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'poultryID' => 'required|exists:poultries,poultryID',
            'locationID' => 'required|exists:locations,locationID',
            'measurement_type' => 'required|string|in:kg,unit',
            'measurement_value' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'item_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = Auth::user();
            
            // Verify the location belongs to the user's company
            $location = Location::find($request->locationID);
            if (!$location || $location->companyID != $user->companyID) {
                return response()->json(['message' => 'Invalid location selected'], 400);
            }
            
            $item = new Item();
            $item->poultryID = $request->poultryID;
            $item->userID = $user->userID;
            $item->locationID = $request->locationID;
            $item->measurement_type = $request->measurement_type;
            $item->measurement_value = $request->measurement_value;
            $item->price = $request->price;
            
            // Handle image upload
            if ($request->hasFile('item_image')) {
                $imagePath = $request->file('item_image')->store('item_images', 'public');
                $item->item_image = $imagePath;
            }
            
            $item->save();
            
            // Load relationships
            $item->load(['poultry', 'location']);
            
            // Format the response
            $formattedItem = [
                'itemID' => $item->itemID,
                'poultryID' => $item->poultryID,
                'poultry_name' => $item->poultry->poultry_name,
                'poultry_image' => $item->poultry->poultry_image ? asset('storage/' . $item->poultry->poultry_image) : null,
                'userID' => $item->userID,
                'locationID' => $item->locationID,
                'location_name' => $item->location ? $item->location->company_address : null,
                'measurement_type' => $item->measurement_type,
                'measurement_value' => $item->measurement_value,
                'price' => $item->price,
                'item_image' => $item->item_image ? asset('storage/' . $item->item_image) : null,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at
            ];
            
            return response()->json([
                'message' => 'Item created successfully',
                'item' => $formattedItem
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating item: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create item', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified item.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $item = Item::with(['poultry', 'location'])->find($id);
            
            if (!$item) {
                return response()->json(['message' => 'Item not found'], 404);
            }
            
            // Check if the item belongs to the user's company
            if ($item->userID !== $user->userID && $user->role !== 'admin') {
                $itemUser = User::find($item->userID);
                if (!$itemUser || $itemUser->companyID !== $user->companyID) {
                    return response()->json(['message' => 'Unauthorized to access this item'], 403);
                }
            }
            
            // Format the response
            $formattedItem = [
                'itemID' => $item->itemID,
                'poultryID' => $item->poultryID,
                'poultry_name' => $item->poultry->poultry_name,
                'poultry_image' => $item->poultry->poultry_image ? asset('storage/' . $item->poultry->poultry_image) : null,
                'userID' => $item->userID,
                'locationID' => $item->locationID,
                'location_name' => $item->location ? $item->location->company_address : null,
                'measurement_type' => $item->measurement_type,
                'measurement_value' => $item->measurement_value,
                'price' => $item->price,
                'item_image' => $item->item_image ? asset('storage/' . $item->item_image) : null,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at
            ];
            
            return response()->json($formattedItem);
        } catch (\Exception $e) {
            Log::error('Error fetching item: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch item', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified item in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        
        $validator = Validator::make($request->all(), [
            'poultryID' => 'required|exists:poultries,poultryID',
            'locationID' => 'required|exists:locations,locationID',
            'measurement_type' => 'required|string|in:kg,unit',
            'measurement_value' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'item_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $user = Auth::user();
            $item = Item::find($id);
            
            if (!$item) {
                return response()->json(['message' => 'Item not found'], 404);
            }
            
            // Check if the item belongs to the user's company
            if ($item->userID !== $user->userID && $user->role !== 'admin') {
                $itemUser = User::find($item->userID);
                if (!$itemUser || $itemUser->companyID !== $user->companyID) {
                    return response()->json(['message' => 'Unauthorized to update this item'], 403);
                }
            }
            
            // Verify the location belongs to the user's company
            $location = Location::find($request->locationID);
            if (!$location || $location->companyID != $user->companyID) {
                return response()->json(['message' => 'Invalid location selected'], 400);
            }
            
            $item->poultryID = $request->poultryID;
            $item->locationID = $request->locationID;
            $item->measurement_type = $request->measurement_type;
            $item->measurement_value = $request->measurement_value;
            $item->price = $request->price;
            
            // Handle image upload
            if ($request->hasFile('item_image')) {
                // Delete old image if exists
                if ($item->item_image) {
                    Storage::disk('public')->delete($item->item_image);
                }
                
                $imagePath = $request->file('item_image')->store('item_images', 'public');
                $item->item_image = $imagePath;
            }
            
            $item->save();
            
            // Load relationships
            $item->load(['poultry', 'location']);
            
            // Format the response
            $formattedItem = [
                'itemID' => $item->itemID,
                'poultryID' => $item->poultryID,
                'poultry_name' => $item->poultry->poultry_name,
                'poultry_image' => $item->poultry->poultry_image ? asset('storage/' . $item->poultry->poultry_image) : null,
                'userID' => $item->userID,
                'locationID' => $item->locationID,
                'location_name' => $item->location ? $item->location->company_address : null,
                'measurement_type' => $item->measurement_type,
                'measurement_value' => $item->measurement_value,
                'price' => $item->price,
                'item_image' => $item->item_image ? asset('storage/' . $item->item_image) : null,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at
            ];
            
            return response()->json([
                'message' => 'Item updated successfully',
                'item' => $formattedItem
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating item: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update item', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified item from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        
        try {
            $user = Auth::user();
            $item = Item::find($id);
            
            if (!$item) {
                return response()->json(['message' => 'Item not found'], 404);
            }
            
            // Check if the item belongs to the user's company
            if ($item->userID !== $user->userID && $user->role !== 'admin') {
                $itemUser = User::find($item->userID);
                if (!$itemUser || $itemUser->companyID !== $user->companyID) {
                    return response()->json(['message' => 'Unauthorized to delete this item'], 403);
                }
            }
            
            // Check if the item has associated orders
            if ($item->orders()->count() > 0) {
                return response()->json(['message' => 'Cannot delete item with associated orders'], 400);
            }
            
            // Delete image if exists
            if ($item->item_image) {
                Storage::disk('public')->delete($item->item_image);
            }
            
            $item->delete();
            
            return response()->json(['message' => 'Item deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting item: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete item', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get company locations for dropdown
     *
     * @return \Illuminate\Http\Response
     */
    public function getCompanyLocations()
    {
        
        try {
            $user = Auth::user();
            $locations = Location::where('companyID', $user->companyID)
                ->select('locationID', 'company_address', 'location_type')
                ->get();
            
            return response()->json($locations);
        } catch (\Exception $e) {
            Log::error('Error fetching company locations: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch company locations', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get item statistics
     *
     * @return \Illuminate\Http\Response
     */
    public function getStats()
    {
        
        try {
            $user = Auth::user();
            
            // Get all items for the company
            $items = Item::whereHas('user', function($query) use ($user) {
                $query->where('companyID', $user->companyID);
            })->get();
            
            // Calculate statistics
            $totalItems = $items->count();
            $totalKg = $items->where('measurement_type', 'kg')->sum('measurement_value');
            $totalUnits = $items->where('measurement_type', 'unit')->sum('measurement_value');
            $totalValue = $items->sum(function($item) {
                return $item->price * $item->measurement_value;
            });
            
            return response()->json([
                'total_items' => $totalItems,
                'total_kg' => round($totalKg, 2),
                'total_units' => round($totalUnits, 0),
                'total_value' => round($totalValue, 2)
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching item stats: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch item statistics', 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get poultry types for item creation/filtering
     *
     * @return \Illuminate\Http\Response
     */
    /*
    public function getPoultryTypes()
    {
        
        try {
            $poultryTypes = Poultry::select('poultryID', 'poultry_name', 'poultry_image')->get();
            
            // Format poultry image URLs
            $formattedPoultryTypes = $poultryTypes->map(function($poultry) {
                return [
                    'poultryID' => $poultry->poultryID,
                    'poultry_name' => $poultry->poultry_name,
                    'poultry_image' => $poultry->poultry_image ? asset('storage/' . $poultry->poultry_image) : null
                ];
            });
            
            return response()->json($formattedPoultryTypes);
        } catch (\Exception $e) {
            Log::error('Error fetching poultry types: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch poultry types', 'error' => $e->getMessage()], 500);
        }
    }
    */
    
    /**
     * Get locations for the authenticated user's company
     *
     * @return \Illuminate\Http\Response
     */
    public function getLocations()
    {
        
        try {
            $user = Auth::user();
            
            // Get locations for the user's company
            $locations = Location::where('companyID', $user->companyID)
                ->select('locationID', 'company_address', 'location_type')
                ->get();
            
            return response()->json($locations);
        } catch (\Exception $e) {
            Log::error('Error fetching locations: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch locations', 'error' => $e->getMessage()], 500);
        }
    }
}