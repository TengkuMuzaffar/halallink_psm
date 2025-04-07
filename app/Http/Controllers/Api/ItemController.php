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
     * Display a listing of the items with pagination, search, filter and sort.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Start with a base query
            $query = Item::with(['poultry', 'location', 'user'])
                ->whereHas('user', function($q) use ($user) {
                    $q->where('companyID', $user->companyID);
                });
            
            // Apply poultry filter if provided
            if ($request->has('poultryID') && !empty($request->poultryID)) {
                $query->where('poultryID', $request->poultryID);
            }
            
            // Apply location filter if provided
            if ($request->has('locationID') && !empty($request->locationID)) {
                $query->where('locationID', $request->locationID);
            }
            
            // Apply measurement type filter if provided
            if ($request->has('measurement_type') && !empty($request->measurement_type)) {
                $query->where('measurement_type', $request->measurement_type);
            }
            
            // Apply search if provided
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->whereHas('poultry', function($q) use ($searchTerm) {
                        $q->where('poultry_name', 'LIKE', $searchTerm);
                    })
                    ->orWhereHas('location', function($q) use ($searchTerm) {
                        $q->where('company_address', 'LIKE', $searchTerm);
                    });
                });
            }
            
            // Apply sorting
            $sortField = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            
            // Validate sort field to prevent SQL injection
            $allowedSortFields = ['created_at', 'price', 'measurement_value'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'created_at';
            }
            
            $query->orderBy($sortField, $sortDirection);
            
            // Get items with pagination
            $perPage = $request->input('per_page', 10); // Default to 10 items per page
            $page = $request->input('page', 1);
            
            $paginatedItems = $query->paginate($perPage);
            
            // Format the response
            $formattedItems = $paginatedItems->getCollection()->map(function($item) {
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
            
            // Replace the items in the paginator with our formatted items
            $paginatedItems->setCollection($formattedItems);
            
            // Format the response to match what the frontend expects
            return response()->json([
                'success' => true,
                'data' => $paginatedItems->items(),
                'pagination' => [
                    'current_page' => $paginatedItems->currentPage(),
                    'last_page' => $paginatedItems->lastPage(),
                    'per_page' => $paginatedItems->perPage(),
                    'total' => $paginatedItems->total(),
                    'from' => $paginatedItems->firstItem(),
                    'to' => $paginatedItems->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching items: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch items',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get item statistics with filters
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getItemStats(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Start with a base query
            $query = Item::whereHas('user', function($q) use ($user) {
                $q->where('companyID', $user->companyID);
            });
            
            // Apply poultry filter if provided
            if ($request->has('poultryID') && !empty($request->poultryID)) {
                $query->where('poultryID', $request->poultryID);
            }
            
            // Apply location filter if provided
            if ($request->has('locationID') && !empty($request->locationID)) {
                $query->where('locationID', $request->locationID);
            }
            
            // Apply measurement type filter if provided
            if ($request->has('measurement_type') && !empty($request->measurement_type)) {
                $query->where('measurement_type', $request->measurement_type);
            }
            
            // Apply search if provided
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->whereHas('poultry', function($q) use ($searchTerm) {
                        $q->where('poultry_name', 'LIKE', $searchTerm);
                    })
                    ->orWhereHas('location', function($q) use ($searchTerm) {
                        $q->where('company_address', 'LIKE', $searchTerm);
                    });
                });
            }
            
            // Get filtered items
            $items = $query->get();
            
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
     * Store a newly created item in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'poultryID' => 'required|exists:poultries,poultryID',
                'locationID' => 'required|exists:locations,locationID',
                'measurement_type' => 'required|string|in:kg,unit',
                'measurement_value' => 'required|numeric|min:0',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0', // Add stock validation
                'item_image' => 'nullable|image|max:2048',
            ]);
        
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
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
            $item->stock = $request->stock; // Add stock field
            
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
                'stock' => $item->stock, // Include stock in response
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
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'poultryID' => 'required|exists:poultries,poultryID',
                'locationID' => 'required|exists:locations,locationID',
                'measurement_type' => 'required|string|in:kg,unit',
                'measurement_value' => 'required|numeric|min:0',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0', // Add stock validation
                'item_image' => 'nullable|image|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
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
            $item->stock = $request->stock; // Add stock field
            
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
                'stock' => $item->stock, // Include stock in response
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
     * Get locations for the authenticated user's company
     *
     * @return \Illuminate\Http\Response
     */
    public function getCompanyLocations()
    {
        try {
            $user = Auth::user();
            
            // Get only supplier locations for the user's company
            $locations = Location::where('companyID', $user->companyID)
                ->where('location_type', 'supplier')
                ->select('locationID', 'company_address', 'location_type')
                ->get();
            
            return response()->json($locations);
        } catch (\Exception $e) {
            Log::error('Error fetching locations: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch locations', 'error' => $e->getMessage()], 500);
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