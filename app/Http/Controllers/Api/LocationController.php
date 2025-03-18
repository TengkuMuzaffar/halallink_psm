<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $locations = Location::query();
            
            // Filter by company if needed
            if ($request->has('companyID')) {
                $locations->where('companyID', $request->companyID);
            }
            
            return response()->json($locations->get());
        } catch (\Exception $e) {
            Log::error('Error fetching locations: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch locations', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'companyID' => 'required|exists:companies,companyID',
                'company_address' => 'required|string',
                'location_type' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $location = Location::create($request->all());
            return response()->json($location, 201);
        } catch (\Exception $e) {
            Log::error('Error creating location: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create location', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $location = Location::findOrFail($id);
            return response()->json($location);
        } catch (\Exception $e) {
            Log::error('Error fetching location: ' . $e->getMessage());
            return response()->json(['message' => 'Location not found', 'error' => $e->getMessage()], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $location = Location::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'companyID' => 'exists:companies,companyID',
                'company_address' => 'string',
                'location_type' => 'string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $location->update($request->all());
            return response()->json($location);
        } catch (\Exception $e) {
            Log::error('Error updating location: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update location', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $location = Location::findOrFail($id);
            $location->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Error deleting location: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete location', 'error' => $e->getMessage()], 500);
        }
    }
}
