<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Item;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Payment;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SmeOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
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
        })->where('location_type', 'kitchen')->get();

        if ($smeLocations->isEmpty()) {
            $this->command->error('No SME locations found. Please run LocationSeeder first.');
            return;
        }

        $this->command->info('Creating orders for SME users...');
        
        // For each SME user, create 3-5 random orders
        foreach ($smeUsers as $user) {
            $numOrders = rand(3, 5);
            $this->command->info("Creating {$numOrders} orders for user {$user->name}");
            
            $progressBar = $this->command->getOutput()->createProgressBar($numOrders);
            
            for ($i = 0; $i < $numOrders; $i++) {
                // Create order
                $order = new Order();
                $order->userID = $user->userID;
                
                // Get a location for this user's company
                $userLocation = $smeLocations->where('companyID', $user->companyID)->first();
                if (!$userLocation) {
                    continue; // Skip if no location found for this user's company
                }
                
                $order->locationID = $userLocation->locationID;
                
                // Randomly decide if order is completed or not (80% chance of completed)
                $isCompleted = (rand(1, 100) <= 80);
                $order->order_status = $isCompleted ? 'paid' : 'draft';
                
                // Set timestamps for completed orders with random month and day
                if ($isCompleted) {
                    // Generate a random date within the last year
                    $randomMonth = rand(1, 12);  // Random month (1-12)
                    $randomDay = rand(1, 28);    // Random day (1-28 to avoid month boundary issues)
                    $randomHour = rand(8, 20);   // Random hour (8am-8pm)
                    $randomMinute = rand(0, 59); // Random minute
                    
                    $orderDate = Carbon::create(
                        Carbon::now()->year,  // Current year
                        $randomMonth,
                        $randomDay,
                        $randomHour,
                        $randomMinute
                    );
                    
                    // Ensure the date is not in the future
                    if ($orderDate->gt(Carbon::now())) {
                        $orderDate->subYear();  // Use previous year if date would be in future
                    }
                    
                    $order->order_timestamp = $orderDate;
                }
                
                $order->save();
                
                // Add 1-5 random items to cart
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
                        'item_cart_delivered' => false,
                    ]);
                    
                    // Reduce stock if order is completed
                    if ($isCompleted) {
                        $item->stock = max(0, $item->stock - $quantity);
                        $item->save();
                    }
                }
                
                // Create payment for completed orders
                if ($isCompleted) {
                    $payment = Payment::create([
                        'payment_amount' => $orderTotal,
                        'payment_status' => 'completed',
                        'payment_reference' => 'HL-' . strtoupper(Str::random(8)),
                        'bill_code' => strtolower(Str::random(8)),
                        'transaction_id' => 'TP' . Carbon::now()->format('Ymd') . rand(100000000, 999999999),
                        'payment_timestamp' => $order->order_timestamp,
                    ]);
                    
                    $order->paymentID = $payment->paymentID;
                    $order->save();
                }
                
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->command->info("");
        }
        
        $this->command->info("\nCreated orders for SME users successfully.");
    }
}