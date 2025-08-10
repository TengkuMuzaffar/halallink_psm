<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Order;
use App\Models\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }
    
    public function test_get_cart_items_returns_empty_cart_when_no_draft_order()
    {
        $response = $this->getJson('/api/cart');
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Cart is empty',
            'cart_items' => [],
            'cart_count' => 0,
            'cart_total' => 0
        ]);
    }
    
    public function test_get_cart_items_returns_items_when_draft_order_exists()
    {
        $order = Order::factory()->create([
            'userID' => $this->user->userID,
            'order_status' => 'draft'
        ]);
        
        $item = Item::factory()->create();
        
        Cart::factory()->create([
            'orderID' => $order->orderID,
            'itemID' => $item->itemID,
            'quantity' => 2,
            'price_at_purchase' => 10.00
        ]);
        
        $response = $this->getJson('/api/cart');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'cart_items',
            'cart_count',
            'cart_total'
        ]);
        $response->assertJson([
            'success' => true,
            'cart_count' => 2,
            'cart_total' => 20.00
        ]);
    }
    
    public function test_add_to_cart_creates_draft_order_if_none_exists()
    {
        $item = Item::factory()->create(['stock' => 10]);
        
        $response = $this->postJson('/api/cart/add', [
            'itemID' => $item->itemID,
            'quantity' => 2
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'userID' => $this->user->userID,
            'order_status' => 'draft'
        ]);
        $this->assertDatabaseHas('carts', [
            'itemID' => $item->itemID,
            'quantity' => 2
        ]);
    }
    
    public function test_add_to_cart_updates_quantity_if_item_already_in_cart()
    {
        $order = Order::factory()->create([
            'userID' => $this->user->userID,
            'order_status' => 'draft'
        ]);
        
        $item = Item::factory()->create(['stock' => 10]);
        
        Cart::factory()->create([
            'orderID' => $order->orderID,
            'itemID' => $item->itemID,
            'quantity' => 2
        ]);
        
        $response = $this->postJson('/api/cart/add', [
            'itemID' => $item->itemID,
            'quantity' => 3
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('carts', [
            'orderID' => $order->orderID,
            'itemID' => $item->itemID,
            'quantity' => 5
        ]);
    }
    
    public function test_update_cart_item_quantity()
    {
        $order = Order::factory()->create([
            'userID' => $this->user->userID,
            'order_status' => 'draft'
        ]);
        
        $item = Item::factory()->create(['stock' => 10]);
        
        $cartItem = Cart::factory()->create([
            'orderID' => $order->orderID,
            'itemID' => $item->itemID,
            'quantity' => 2
        ]);
        
        $response = $this->putJson('/api/cart/update/' . $cartItem->cartID, [
            'quantity' => 4
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('carts', [
            'cartID' => $cartItem->cartID,
            'quantity' => 4
        ]);
    }
    
    public function test_remove_cart_item()
    {
        $order = Order::factory()->create([
            'userID' => $this->user->userID,
            'order_status' => 'draft'
        ]);
        
        $item = Item::factory()->create();
        
        $cartItem = Cart::factory()->create([
            'orderID' => $order->orderID,
            'itemID' => $item->itemID
        ]);
        
        $response = $this->deleteJson('/api/cart/remove/' . $cartItem->cartID);
        
        $response->assertStatus(200);
        $this->assertDatabaseMissing('carts', ['cartID' => $cartItem->cartID]);
    }
    
    public function test_clear_cart()
    {
        $order = Order::factory()->create([
            'userID' => $this->user->userID,
            'order_status' => 'draft'
        ]);
        
        Cart::factory()->count(3)->create(['orderID' => $order->orderID]);
        
        $response = $this->deleteJson('/api/cart/clear');
        
        $response->assertStatus(200);
        $this->assertDatabaseMissing('carts', ['orderID' => $order->orderID]);
    }
}