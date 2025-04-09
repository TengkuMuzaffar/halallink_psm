<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    /**
     * Display a listing of vehicles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Vehicle::with('company');
        
        // Filter by company if user is not admin
        $user = Auth::user();
        if ($user && $user->role !== 'admin') {
            $query->where('companyID', $user->companyID);
        }
        
        // Search by vehicle plate
        if ($request->has('search') && !empty($request->search)) {
            $query->where('vehicle_plate', 'like', '%' . $request->search . '%');
        }
        
        // Filter by load weight
        if ($request->has('min_weight') && is_numeric($request->min_weight)) {
            $query->where('vehicle_load_weight', '>=', $request->min_weight);
        }
        
        if ($request->has('max_weight') && is_numeric($request->max_weight)) {
            $query->where('vehicle_load_weight', '<=', $request->max_weight);
        }
        
        // Sort
        $sortField = $request->input('sort_field', 'vehicleID');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Paginate
        $perPage = $request->input('per_page', 10);
        $vehicles = $query->paginate($perPage);
        
        return response()->json([
            'vehicles' => $vehicles,
            'stats' => [
                'total' => Vehicle::count(),
                'average_weight' => Vehicle::avg('vehicle_load_weight') ?? 0
            ]
        ]);
    }

    /**
     * Store a newly created vehicle in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'companyID' => 'required|exists:companies,companyID',
            'vehicle_plate' => 'required|string|max:20|unique:vehicles',
            'vehicle_load_weight' => 'required|numeric|min:0'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $vehicle = Vehicle::create($request->all());
        
        return response()->json([
            'message' => 'Vehicle created successfully',
            'vehicle' => $vehicle
        ], 201);
    }

    /**
     * Display the specified vehicle.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $vehicle = Vehicle::with('company')->findOrFail($id);
        
        return response()->json(['vehicle' => $vehicle]);
    }

    /**
     * Update the specified vehicle in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'companyID' => 'required|exists:companies,companyID',
            'vehicle_plate' => 'required|string|max:20|unique:vehicles,vehicle_plate,' . $id . ',vehicleID',
            'vehicle_load_weight' => 'required|numeric|min:0'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $vehicle->update($request->all());
        
        return response()->json([
            'message' => 'Vehicle updated successfully',
            'vehicle' => $vehicle
        ]);
    }

    /**
     * Remove the specified vehicle from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();
        
        return response()->json(['message' => 'Vehicle deleted successfully']);
    }
    
    /**
     * Get companies for dropdown.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCompanies()
    {
        $companies = Company::select('companyID', 'company_name')->get();
        
        return response()->json(['companies' => $companies]);
    }
}