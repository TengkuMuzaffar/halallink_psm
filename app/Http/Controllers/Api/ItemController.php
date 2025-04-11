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
            $query = Item::with(['poultry', 'location', 'slaughterhouse', 'user'])
                ->whereHas('user', function($q) use ($user) {
                    $q->where('companyID', $user->companyID);
                });
            
            // Apply poultry filter
            if ($request->has('poultryID') && !empty($request->poultryID)) {
                $query->where('poultryID', $request->poultryID);
            }
            
            // Apply company location filter
            if ($request->has('locationID') && !empty($request->locationID)) {
                $query->where('locationID', $request->locationID);
            }

            // Apply slaughterhouse location filter
            if ($request->has('slaughterhouse_locationID') && !empty($request->slaughterhouse_locationID)) {
                $query->where('slaughterhouse_locationID', $request->slaughterhouse_locationID);
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
                    })
                    ->orWhereHas('slaughterhouse', function($q) use ($searchTerm) {
                        $q->where('company_address', 'LIKE', $searchTerm);
                    });
                });
            }
            
            // Apply measurement type filter if provided
            if ($request->has('measurement_type') && !empty($request->measurement_type)) {
                $query->where('measurement_type', $request->measurement_type);
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
                    'stock' => $item->stock, // Include stock in response
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
                'slaughterhouse_locationID' => 'nullable|exists:locations,locationID',  // Add this line
                'measurement_type' => 'required|string|in:kg,unit',
                'measurement_value' => 'required|numeric|min:0',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
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
            $item->slaughterhouse_locationID = $request->slaughterhouse_locationID;  // Add this line
            $item->measurement_type = $request->measurement_type;
            $item->measurement_value = $request->measurement_value;
            $item->price = $request->price;
            $item->stock = $request->stock;
            
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
            
            // Load the item with its cart items to check for orders
            $item = Item::with(['cartItems'])->find($id);
            
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
            
            // Check if the item has associated cart items
            if ($item->cartItems()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete item that has been ordered',
                    'error' => 'Item exists in cart or orders'
                ], 400);
            }
            
            // Delete image if exists
            if ($item->item_image) {
                $imagePath = storage_path('app/public/' . $item->item_image);
                if (file_exists($imagePath)) {
                    Storage::disk('public')->delete($item->item_image);
                }
            }
            
            $item->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Item deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error deleting item: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete item',
                'error' => $e->getMessage()
            ], 500);
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
            $locations = Location::where('companyID', $user->companyID)
            ->where('location_type', 'supplier')
            ->get();
            
            return response()->json([
                'success' => true,
                'data' => $locations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch locations'
            ], 500);
        }
    }
    

    /**
     * Get locations for the authenticated user's company
     *
     * @return \Illuminate\Http\Response
     */
    public function getSlaughterhouseLocations()
    {
        try {
            $user = Auth::user();
            
            // Get only slaughterhouse locations for the user's company
            $locations = Location::where('location_type', 'slaughterhouse')
                ->select('locationID', 'company_address', 'location_type')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $locations
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching slaughterhouse locations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch slaughterhouse locations'
            ], 500);
        }
    }
}