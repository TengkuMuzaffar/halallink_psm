<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Payment;
use App\Http\Controllers\Api\ToyyibPayController;
use Illuminate\Support\Facades\DB;

class CheckpointCreatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing checkpoints and related data
        DB::table('trips')->delete();
        DB::table('tasks')->delete();
        DB::table('checkpoints')->delete();
        
        // Get all orders with completed payments
        $orders = Order::whereHas('payment', function($query) {
            $query->where('payment_status', 'completed');
        })->get();
        
        if ($orders->isEmpty()) {
            $this->command->error('No orders with completed payments found. Please run SmeOrderSeeder first.');
            return;
        }
        
        $this->command->info("Creating checkpoints for {$orders->count()} orders with completed payments...");
        $progressBar = $this->command->getOutput()->createProgressBar($orders->count());
        
        // Create controller instance
        $controller = new ToyyibPayController();
        
        foreach ($orders as $order) {
            try {
                // Call the createCheckpoints method
                $controller->createCheckpoints($order);
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->command->error("Error creating checkpoints for order #{$order->orderID}: {$e->getMessage()}");
            }
        }
        
        $progressBar->finish();
        $this->command->info("\nCheckpoints created successfully.");
    }
}