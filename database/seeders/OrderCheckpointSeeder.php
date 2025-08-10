<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Item;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Payment;
use App\Models\Checkpoint;
use App\Models\Task;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OrderCheckpointSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        Task::query()->delete();
        Checkpoint::query()->delete();
        Cart::query()->delete();
        Order::query()->delete();
        Payment::query()->delete();

        // Get SME users (customers)
        $smeUsers = User::whereHas('company', function($query) {
            $query->where('company_type', 'sme');
        })->get();

        if ($smeUsers->isEmpty()) {
            $this->command->error('No SME users found. Please run UserCompanySeeder first.');
            return;
        }

        // Get items with stock
        $items = Item::where('stock', '>', 0)->get();

        if ($items->isEmpty()) {
            $this->command->error('No items found. Please run ItemSeeder first.');
            return;
        }

        // Get SME locations (delivery destinations)
        $smeLocations = Location::whereHas('company', function($query) {
            $query->where('company_type', 'sme');
        })->get();

        if ($smeLocations->isEmpty()) {
            $this->command->error('No SME locations found. Please run LocationSeeder first.');
            return;
        }

        // Create different order statuses
        $orderStatuses = ['draft', 'paid', 'processing', 'complete'];
        
        // Create 50 orders with different statuses
        $this->command->info('Creating orders and checkpoints...');
        $progressBar = $this->command->getOutput()->createProgressBar(50);
        
        for ($i = 0; $i < 50; $i++) {
            $user = $smeUsers->random();
            $status = $orderStatuses[array_rand($orderStatuses)];
            
            // Create order
            $order = new Order();
            $order->userID = $user->userID;
            $order->locationID = $smeLocations->where('companyID', $user->companyID)->random()->locationID;
            $order->order_status = $status;
            
            // Set timestamps based on status
            if ($status != 'draft') {
                $orderDate = Carbon::now()->subDays(rand(1, 30));
                $order->order_timestamp = $orderDate;
                
                if ($status == 'complete') {
                    $order->deliver_timestamp = $orderDate->copy()->addDays(rand(1, 5));
                }
                
                // Create payment for non-draft orders
                $payment = Payment::create([
                    'payment_amount' => 0, // Will be calculated later
                    'payment_status' => 'success',
                    'payment_reference' => 'REF' . Str::random(8),
                    'bill_code' => 'BILL' . Str::random(8),
                    'transaction_id' => 'TRX' . Str::random(10),
                    'payment_timestamp' => $orderDate,
                ]);
                
                $order->paymentID = $payment->paymentID;
            }
            
            $order->save();
            
            // Add 1-5 items to cart
            $orderTotal = 0;
            $selectedItems = $items->random(rand(1, 5));
            
            foreach ($selectedItems as $item) {
                $quantity = rand(1, 5);
                $priceAtPurchase = $item->price;
                $orderTotal += $priceAtPurchase * $quantity;
                
                Cart::create([
                    'itemID' => $item->itemID,
                    'orderID' => $order->orderID,
                    'quantity' => $quantity,
                    'price_at_purchase' => $priceAtPurchase,
                ]);
                
                // Reduce stock if order is not draft
                if ($status != 'draft') {
                    $item->stock = max(0, $item->stock - $quantity);
                    $item->save();
                }
                
                // Create checkpoints for paid and processing orders
                if (in_array($status, ['paid', 'processing', 'complete'])) {
                    // Create checkpoints (1-4 based on order status)
                    $maxCheckpoint = $status == 'paid' ? 2 : ($status == 'processing' ? 3 : 4);
                    
                    for ($checkpointNum = 1; $checkpointNum <= $maxCheckpoint; $checkpointNum++) {
                        // Determine location based on checkpoint number
                        $locationID = null;
                        
                        if ($checkpointNum == 1) {
                            // Checkpoint 1: Item's location (supplier)
                            $locationID = $item->locationID;
                        } else if ($checkpointNum == 2) {
                            // Checkpoint 2: Slaughterhouse location
                            $locationID = $item->slaughterhouse_locationID;
                        } else if ($checkpointNum == 3) {
                            // Checkpoint 3: Slaughterhouse location (same as 2 for simplicity)
                            $locationID = $item->slaughterhouse_locationID;
                        } else if ($checkpointNum == 4) {
                            // Checkpoint 4: Order's delivery location
                            $locationID = $order->locationID;
                        }
                        
                        // Get company ID from location
                        $location = Location::find($locationID);
                        $companyID = $location ? $location->companyID : null;
                        
                        // Create checkpoint without deliveryID
                        Checkpoint::create([
                            'orderID' => $order->orderID,
                            'locationID' => $locationID,
                            'companyID' => $companyID,
                            'arrange_number' => $checkpointNum,
                        ]);
                    }
                }
            }
            
            // Update payment amount
            if ($status != 'draft' && isset($payment)) {
                $payment->payment_amount = $orderTotal;
                $payment->save();
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->command->info("\nCreated 50 orders with various statuses and their associated checkpoints.");
    }
}