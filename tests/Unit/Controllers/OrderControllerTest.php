<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Order;
use App\Models\Location;
use App\Models\Cart;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    protected $company;
    protected $location;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create(['company_type' => 'sme']);
        $this->location = Location::factory()->create(['companyID' => $this->company->companyID]);
        $this->user = User::factory()->create([
            'companyID' => $this->company->companyID,
            'role' => 'admin'
        ]);
        $this->actingAs($this->user);
    }
    
    public function test_index_returns_orders_for_company_locations()
    {
        Order::factory()->count(3)->create([
            'userID' => $this->user->userID,
            'locationID' => $this->location->locationID
        ]);
        
        $response = $this->getJson('/api/orders');
        
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data',
            'pagination'
        ]);
    }
    
    public function test_show_returns_order_details()
    {
        $order = Order::factory()->create([
            'userID' => $this->user->userID,
            'locationID' => $this->location->locationID,
            'companyID' => $this->company->companyID
        ]);
        
        $item = Item::factory()->create();
        Cart::factory()->create([
            'orderID' => $order->orderID,
            'itemID' => $item->itemID,
            'quantity' => 2,
            'price_at_purchase' => $item->price
        ]);
        
        $response = $this->getJson("/api/orders/{$order->orderID}");
        
        $response->assertStatus(200);
        $response->assertJson([
            'orderID' => $order->orderID,
            'userID' => $this->user->userID,
            'companyID' => $this->company->companyID
        ]);
        
        // Assert that the response includes the related data
        $response->assertJsonStructure([
            'user',
            'items' => [
                '*' => [
                    'itemID',
                    'poultry',
                    'company'
                ]
            ]
        ]);
    }
}