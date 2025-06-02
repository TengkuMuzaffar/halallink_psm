<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Item;
use App\Models\Poultry;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MarketplaceControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }
    
    public function test_get_items_returns_available_items()
    {
        // Create broiler company
        $broilerCompany = Company::factory()->create(['company_type' => 'broiler']);
        $broilerUser = User::factory()->create(['companyID' => $broilerCompany->companyID]);
        
        // Create locations
        $location = Location::factory()->create(['companyID' => $broilerCompany->companyID]);
        
        // Create poultry types
        $poultry1 = Poultry::factory()->create(['poultry_name' => 'Chicken']);
        $poultry2 = Poultry::factory()->create(['poultry_name' => 'Duck']);
        
        // Create items
        Item::factory()->create([
            'userID' => $broilerUser->userID,
            'poultryID' => $poultry1->poultryID,
            'locationID' => $location->locationID,
            'stock' => 100,
            'item_price' => 10.99
        ]);
        
        Item::factory()->create([
            'userID' => $broilerUser->userID,
            'poultryID' => $poultry2->poultryID,
            'locationID' => $location->locationID,
            'stock' => 50,
            'item_price' => 15.99
        ]);
        
        $response = $this->getJson('/api/marketplace/items');
        
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonStructure([
            'data',
            'pagination'
        ]);
    }
    
    public function test_get_items_filters_by_search_term()
    {
        // Create test data
        $broilerCompany = Company::factory()->create(['company_type' => 'broiler', 'company_name' => 'ABC Poultry']);
        $broilerUser = User::factory()->create(['companyID' => $broilerCompany->companyID]);
        $location = Location::factory()->create(['companyID' => $broilerCompany->companyID]);
        $poultry = Poultry::factory()->create(['poultry_name' => 'Chicken']);
        
        Item::factory()->create([
            'userID' => $broilerUser->userID,
            'poultryID' => $poultry->poultryID,
            'locationID' => $location->locationID,
            'item_name' => 'Premium Chicken',
            'stock' => 100
        ]);
        
        Item::factory()->create([
            'userID' => $broilerUser->userID,
            'poultryID' => $poultry->poultryID,
            'locationID' => $location->locationID,
            'item_name' => 'Regular Duck',
            'stock' => 50
        ]);
        
        $response = $this->getJson('/api/marketplace/items?search=Premium');
        
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJson([
            'data' => [
                ['item_name' => 'Premium Chicken']
            ]
        ]);
    }
    
    public function test_get_items_filters_by_poultry_type()
    {
        // Create test data
        $broilerCompany = Company::factory()->create(['company_type' => 'broiler']);
        $broilerUser = User::factory()->create(['companyID' => $broilerCompany->companyID]);
        $location = Location::factory()->create(['companyID' => $broilerCompany->companyID]);
        
        $poultry1 = Poultry::factory()->create(['poultry_name' => 'Chicken']);
        $poultry2 = Poultry::factory()->create(['poultry_name' => 'Duck']);
        
        Item::factory()->create([
            'userID' => $broilerUser->userID,
            'poultryID' => $poultry1->poultryID,
            'locationID' => $location->locationID,
            'stock' => 100
        ]);
        
        Item::factory()->create([
            'userID' => $broilerUser->userID,
            'poultryID' => $poultry2->poultryID,
            'locationID' => $location->locationID,
            'stock' => 50
        ]);
        
        $response = $this->getJson('/api/marketplace/items?poultry_type=' . $poultry1->poultryID);
        
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJson([
            'data' => [
                ['poultryID' => $poultry1->poultryID]
            ]
        ]);
    }
    
    public function test_get_poultry_types_returns_all_poultry_types()
    {
        Poultry::factory()->count(3)->create();
        
        $response = $this->getJson('/api/marketplace/poultry-types');
        
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }
}