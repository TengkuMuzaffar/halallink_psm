<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Verify;
use App\Models\Cart;
use App\Models\Location; // Added import for Location model
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Get tasks with verification status check
        $query = Task::query()
            ->select(
                'tasks.*',
                'trips.deliveryID',
                'trips.orderID' // Added trips.orderID here
            )
            ->join('verifies', 'tasks.checkID', '=', 'verifies.checkID')
            ->join('checkpoints', 'tasks.checkID', '=', 'checkpoints.checkID')
            ->leftJoin('trips', function($join) {
                $join->on('tasks.checkID', '=', 'trips.start_checkID')
                    ->orOn('tasks.checkID', '=', 'trips.end_checkID');
            })
            // Remove the problematic joins
            ->where('verifies.verify_status', 'complete')
            ->orderBy('tasks.start_timestamp', 'asc'); // Oldest first (FIFO)
        
        // Apply search if provided
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('tasks.checkID', 'like', "%{$searchTerm}%")
                  ->orWhere('tasks.task_type', 'like', "%{$searchTerm}%")
                  ->orWhere('trips.deliveryID', 'like', "%{$searchTerm}%")
                  ->orWhere('trips.orderID', 'like', "%{$searchTerm}%"); // Added trips.orderID to search
            });
        }
        
        // Paginate results
        $tasks = $query->paginate(10);
        
        // Process each task to add additional information
        foreach ($tasks as $task) {
            // Get checkpoint data
            $checkpoint = \App\Models\Checkpoint::where('checkID', $task->checkID)->first();
            
            if ($checkpoint && !empty($checkpoint->item_record)) {
                // Assign item_record directly if it's already an array,
                // otherwise decode if it's a JSON string.
                $itemIds = is_string($checkpoint->item_record) ? json_decode($checkpoint->item_record, true) : $checkpoint->item_record;
                
                if (is_array($itemIds) && count($itemIds) > 0) {
                    // Get the first itemID for simplicity
                    $itemId = $itemIds[0];
                    
                    // Fetch the item and poultry information
                    $item = \App\Models\Item::where('itemID', $itemId)->first();
                    if ($item) {
                        $poultry = \App\Models\Poultry::where('poultryID', $item->poultryID)->first();
                        if ($poultry) {
                            $task->item_name = $poultry->poultry_name;
                        }

                        // Add item details: measurement_value, measurement_type, locationID
                        $task->measurement_value = $item->measurement_value;
                        $task->measurement_type = $item->measurement_type;
                        // $task->supplier_locationID = $item->locationID; // locationID from item

                        // Fetch supplier address using locationID from item
                        if ($item->locationID) {
                            $supplierLocation = \App\Models\Location::where('locationID', $item->locationID)->first();
                            if ($supplierLocation) {
                                $task->supplier_address = $supplierLocation->company_address;
                            } else {
                                $task->supplier_address = null;
                            }
                        } else {
                            $task->supplier_address = null;
                        }

                        // Fetch quantity from cart
                        if ($task->orderID) { // Ensure orderID is available from the trips join
                            $cartEntry = \App\Models\Cart::where('orderID', $task->orderID)
                                                        ->where('itemID', $item->itemID)
                                                        ->first();
                            if ($cartEntry) {
                                $task->item_quantity = $cartEntry->quantity;
                            } else {
                                $task->item_quantity = null; 
                            }
                        } else {
                            $task->item_quantity = null; 
                        }
                    } else { // if $item is not found
                        $task->measurement_value = null;
                        $task->measurement_type = null;
                        $task->supplier_address = null;
                        $task->item_quantity = null;
                    }
                } else { // if itemIds is not an array or is empty
                    $task->item_name = null;
                    $task->measurement_value = null;
                    $task->measurement_type = null;
                    $task->supplier_address = null;
                    $task->item_quantity = null;
                }
            } else { // if checkpoint or item_record is not found/empty
                $task->item_name = null;
                $task->measurement_value = null;
                $task->measurement_type = null;
                $task->supplier_address = null;
                $task->item_quantity = null;
            }
            
            // Get order information if deliveryID exists - This block can now be removed
            // as orderID is selected in the main query if available through the trip.
            // if ($task->deliveryID && !$task->orderID) { // Check if orderID isn't already set
            //     $order = \App\Models\Order::where('deliveryID', $task->deliveryID)->first();
            //     if ($order) {
            //         $task->orderID = $order->orderID;
            //     }
            // }
        }
        
        return response()->json($tasks);
    }

    /**
     * Update the specified task.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validate request
        $request->validate([
            'task_status' => 'sometimes|string',
            'notes' => 'sometimes|string',
            'start_timestamp' => 'sometimes|date',
            'finish_timestamp' => 'sometimes|date'
        ]);

        // Find the task
        $task = Task::findOrFail($id);
        
        // Check if the task's checkID exists in verifies table with complete status
        $verifyExists = Verify::where('checkID', $task->checkID)
                             ->where('verify_status', 'complete')
                             ->exists();
        
        if (!$verifyExists) {
            return response()->json([
                'message' => 'Cannot update task. Verification is not complete.'
            ], 400);
        }
        
        // Update task
        $task->update($request->all());
        
        // Return only success message without the task data
        return response()->json([
            'message' => 'Task updated successfully'
        ]);
    }
}