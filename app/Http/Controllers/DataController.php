<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DataController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Implement your data storage logic here
        Log::info('Data store method called');
        
        return response()->json([
            'message' => 'Data stored successfully',
            'data' => $request->all()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Implement your data update logic here
        Log::info('Data update method called for ID: ' . $id);
        
        return response()->json([
            'message' => 'Data updated successfully',
            'id' => $id,
            'data' => $request->all()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Implement your data deletion logic here
        Log::info('Data delete method called for ID: ' . $id);
        
        return response()->json([
            'message' => 'Data deleted successfully',
            'id' => $id
        ]);
    }
}