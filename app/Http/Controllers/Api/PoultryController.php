<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poultry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PoultryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Handle filtering if poultry_name parameter is provided
        if ($request->has('poultry_name')) {
            $poultries = Poultry::where('poultry_name', $request->poultry_name)->get();
        } else {
            $poultries = Poultry::all();
        }
        
        // Format image URLs
        $poultries = $poultries->map(function($poultry) {
            $poultryData = $poultry->toArray();
            if ($poultry->poultry_image) {
                // Remove the /storage/ prefix if it exists
                $imagePath = str_replace('/storage/', '', $poultry->poultry_image);
                $poultryData['poultry_image'] = asset('storage/' . $imagePath);
            }
            return $poultryData;
        });
        
        return response()->json($poultries);
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
}