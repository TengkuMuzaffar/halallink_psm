<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poultry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PoultryController extends Controller
{
    /**
     * Display a listing of the resource with pagination and search.
     */
    public function index(Request $request)
    {
        try {
            // Start with a base query
            $query = Poultry::query();
            
            // Apply search if provided
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = '%' . $request->search . '%';
                $query->where('poultry_name', 'LIKE', $searchTerm);
            }
            
            // Get poultries with pagination
            $perPage = $request->input('per_page', 10); // Default to 10 items per page
            $page = $request->input('page', 1);
            
            $paginatedPoultries = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);
            
            // Format image URLs
            $formattedPoultries = $paginatedPoultries->getCollection()->map(function($poultry) {
                $poultryData = $poultry->toArray();
                if ($poultry->poultry_image) {
                    // Remove the /storage/ prefix if it exists
                    $imagePath = str_replace('/storage/', '', $poultry->poultry_image);
                    $poultryData['poultry_image'] = asset('storage/' . $imagePath);
                }
                return $poultryData;
            });
            
            // Replace the items in the paginator with our formatted items
            $paginatedPoultries->setCollection($formattedPoultries);
            
            // Format the response to match what the frontend expects
            return response()->json([
                'success' => true,
                'data' => $paginatedPoultries->items(),
                'pagination' => [
                    'current_page' => $paginatedPoultries->currentPage(),
                    'last_page' => $paginatedPoultries->lastPage(),
                    'per_page' => $paginatedPoultries->perPage(),
                    'total' => $paginatedPoultries->total(),
                    'from' => $paginatedPoultries->firstItem(),
                    'to' => $paginatedPoultries->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching poultries: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch poultries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get poultry statistics
     */
    public function getStats(Request $request)
    {
        try {
            // Start with a base query
            $query = Poultry::query();
            
            // Apply search if provided
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = '%' . $request->search . '%';
                $query->where('poultry_name', 'LIKE', $searchTerm);
            }
            
            // Get total count
            $total = $query->count();
            
            // Get unique poultry names count
            $uniqueNames = Poultry::distinct('poultry_name')->count('poultry_name');
            
            $stats = [
                'total' => $total,
                'unique_types' => $uniqueNames
            ];
            
            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Error fetching poultry stats: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch poultry statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'poultry_name' => 'required|string|max:255',
            'poultry_image_file' => 'nullable|image|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $poultryData = [
            'poultry_name' => $request->poultry_name,
        ];

        // Handle image upload if file is provided
        if ($request->hasFile('poultry_image_file')) {
            $file = $request->file('poultry_image_file');
            $filename = Str::slug($request->poultry_name) . '-' . time() . '.' . $file->getClientOriginalExtension();
            
            // Store the file in the poultry_image folder
            $path = $file->storeAs('poultry_image', $filename, 'public');
            
            // Set the image path (without /storage/ prefix)
            $poultryData['poultry_image'] = $path;
        }

        $poultry = Poultry::create($poultryData);
        
        // Format response with proper image URL
        $responseData = $poultry->toArray();
        if ($poultry->poultry_image) {
            $responseData['poultry_image'] = asset('storage/' . $poultry->poultry_image);
        }
        
        return response()->json($responseData, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $poultry = Poultry::findOrFail($id);
        
        // Format response with proper image URL
        $responseData = $poultry->toArray();
        if ($poultry->poultry_image) {
            // Remove the /storage/ prefix if it exists
            $imagePath = str_replace('/storage/', '', $poultry->poultry_image);
            $responseData['poultry_image'] = asset('storage/' . $imagePath);
        }
        
        return response()->json($responseData);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'poultry_name' => 'required|string|max:255',
            'poultry_image_file' => 'nullable|image|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $poultry = Poultry::findOrFail($id);
        $poultryData = [
            'poultry_name' => $request->poultry_name,
        ];

        // Handle image upload if file is provided
        if ($request->hasFile('poultry_image_file')) {
            // Delete old image if exists and is stored locally
            if ($poultry->poultry_image) {
                $oldPath = str_replace('/storage/', '', $poultry->poultry_image);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            
            $file = $request->file('poultry_image_file');
            $filename = Str::slug($request->poultry_name) . '-' . time() . '.' . $file->getClientOriginalExtension();
            
            // Store the file in the poultry_image folder
            $path = $file->storeAs('poultry_image', $filename, 'public');
            
            // Set the image path (without /storage/ prefix)
            $poultryData['poultry_image'] = $path;
        }

        $poultry->update($poultryData);
        
        // Format response with proper image URL
        $responseData = $poultry->toArray();
        if ($poultry->poultry_image) {
            // Remove the /storage/ prefix if it exists
            $imagePath = str_replace('/storage/', '', $poultry->poultry_image);
            $responseData['poultry_image'] = asset('storage/' . $imagePath);
        }
        
        return response()->json($responseData);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $poultry = Poultry::findOrFail($id);
        
        // Delete the image file if it exists and is stored locally
        if ($poultry->poultry_image) {
            $path = str_replace('/storage/', '', $poultry->poultry_image);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
        
        $poultry->delete();
        
        return response()->json(['message' => 'Poultry deleted successfully']);
    }

    /**
     * Get unique poultry names for filtering
     */
    public function getUniqueNames()
    {
        try {
            $uniqueNames = Poultry::distinct('poultry_name')
                ->pluck('poultry_name')
                ->toArray();
            
            return response()->json($uniqueNames);
        } catch (\Exception $e) {
            Log::error('Error fetching unique poultry names: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch unique poultry names',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}