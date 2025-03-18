<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poultry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PoultryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $poultries = Poultry::all();
        return response()->json($poultries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'poultry_name' => 'required|string|max:255',
            'poultry_image' => 'nullable|string|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $poultry = Poultry::create($request->all());
        return response()->json($poultry, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $poultry = Poultry::findOrFail($id);
        return response()->json($poultry);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'poultry_name' => 'required|string|max:255',
            'poultry_image' => 'nullable|string|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $poultry = Poultry::findOrFail($id);
        $poultry->update($request->all());
        
        return response()->json($poultry);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $poultry = Poultry::findOrFail($id);
        $poultry->delete();
        
        return response()->json(['message' => 'Poultry deleted successfully']);
    }
}