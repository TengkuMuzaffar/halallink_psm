<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $vehicles = Vehicle::query();
            
            // Filter by company if needed
            if ($request->has('companyID')) {
                $vehicles->where('companyID', $request->companyID);
            }
            
            return response()->json($vehicles->get());
        } catch (\Exception $e) {
            Log::error('Error fetching vehicles: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch vehicles', 'error' => $e->getMessage()], 500);
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
                'vehicle_plate' => 'required|string|unique:vehicles',
                'vehicle_load_weight' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $vehicle = Vehicle::create($request->all());
            return response()->json($vehicle, 201);
        } catch (\Exception $e) {
            Log::error('Error creating vehicle: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create vehicle', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $vehicle = Vehicle::findOrFail($id);
            return response()->json($vehicle);
        } catch (\Exception $e) {
            Log::error('Error fetching vehicle: ' . $e->getMessage());
            return response()->json(['message' => 'Vehicle not found', 'error' => $e->getMessage()], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $vehicle = Vehicle::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'companyID' => 'exists:companies,companyID',
                'vehicle_plate' => 'string|unique:vehicles,vehicle_plate,' . $id . ',vehicleID',
                'vehicle_load_weight' => 'numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $vehicle->update($request->all());
            return response()->json($vehicle);
        } catch (\Exception $e) {
            Log::error('Error updating vehicle: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update vehicle', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $vehicle = Vehicle::findOrFail($id);
            $vehicle->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Error deleting vehicle: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete vehicle', 'error' => $e->getMessage()], 500);
        }
    }
}
