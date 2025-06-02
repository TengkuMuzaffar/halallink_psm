<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Item;
use App\Models\Poultry;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ItemControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    protected $company;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create(['company_type' => 'broiler']);
        $this->user = User::factory()->create([
            'companyID' => $this->company->companyID,
            'role' => 'admin'
        ]);
        $this->actingAs($this->user);
    }
    
    public function test_index_returns_items_for_company()
    {
        Item::factory()->count(3)->create([
            'userID' => $this->user->userID
        ]);
        
        $response = $this->getJson('/api/items');
        
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data',
            'pagination'
        ]);
    }
    
    public function test_store_creates_new_item()
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('item.jpg');
        
        $poultry = Poultry::factory()->create();
        $location = Location::factory()->create(['companyID' => $this->company->companyID]);
        $slaughterhouse = Location::factory()->create();
        
        $response = $this->postJson('/api/items', [
            'poultryID' => $poultry->poultryID,
            'locationID' => $location->locationID,
            'slaughterhouse_locationID' => $slaughterhouse->locationID,
            'item_name' => 'Test Item',
            'item_description' => 'Test Description',
            'item_price' => 10.99,
            'stock' => 100,
            'item_image' => $image
        ]);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('items', [
            'userID' => $this->user->userID,
            'item_name' => 'Test Item',
            'item_price' => 10.99,
            'stock' => 100
        ]);
    }
    
    public function test_show_returns_item_details()
    {
        $item = Item::factory()->create(['userID' => $this->user->userID]);
        
        $response = $this->getJson('/api/items/' . $item->itemID);
        
        $response->assertStatus(200);
        $response->assertJson([
            'itemID' => $item->itemID,
            'item_name' => $item->item_name
        ]);
    }
    
    public function test_update_item_details()
    {
        $item = Item::factory()->create(['userID' => $this->user->userID]);
        
        $response = $this->putJson('/api/items/' . $item->itemID, [
            'item_name' => 'Updated Item Name',
            'item_description' => 'Updated Description',
            'item_price' => 15.99,
            'stock' => 50
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('items', [
            'itemID' => $item->itemID,
            'item_name' => 'Updated Item Name',
            'item_price' => 15.99,
            'stock' => 50
        ]);
    }
    
    public function test_delete_item()
    {
        $item = Item::factory()->create(['userID' => $this->user->userID]);
        
        $response = $this->deleteJson('/api/items/' . $item->itemID);
        
        $response->assertStatus(200);
        $this->assertSoftDeleted('items', ['itemID' => $item->itemID]);
    }
    
    public function test_update_stock_adjusts_item_stock()
    {
        $item = Item::factory()->create([
            'userID' => $this->user->userID,
            'stock' => 100
        ]);
        
        $response = $this->putJson('/api/items/' . $item->itemID . '/stock', [
            'stock' => 150
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('items', [
            'itemID' => $item->itemID,
            'stock' => 150
        ]);
    }
}