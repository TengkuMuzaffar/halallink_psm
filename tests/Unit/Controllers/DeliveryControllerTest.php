<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Order;
use App\Models\Delivery;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Models\Checkpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeliveryControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $logisticUser;
    protected $logisticCompany;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->logisticCompany = Company::factory()->create(['company_type' => 'logistic']);
        $this->logisticUser = User::factory()->create([
            'companyID' => $this->logisticCompany->companyID,
            'role' => 'admin'
        ]);
        $this->actingAs($this->logisticUser);
    }
    
    public function test_index_returns_pending_deliveries_grouped_by_location()
    {
        // Create test orders, checkpoints, and trips
        $order = Order::factory()->create(['order_status' => 'paid']);
        $checkpoint1 = Checkpoint::factory()->create(['orderID' => $order->orderID, 'arrange_number' => 1]);
        $checkpoint2 = Checkpoint::factory()->create(['orderID' => $order->orderID, 'arrange_number' => 2]);
        Trip::factory()->create([
            'orderID' => $order->orderID,
            'start_checkID' => $checkpoint1->checkID,
            'end_checkID' => $checkpoint2->checkID
        ]);
        
        $response = $this->getJson('/api/deliveries');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'pagination'
        ]);
    }
    
    public function test_get_by_location_returns_deliveries_for_specific_location()
    {
        // Create test location, checkpoints, and trips
        
        $response = $this->getJson('/api/deliveries/location/1');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'pagination'
        ]);
    }
    
    public function test_assign_single_trip_assigns_trip_to_delivery()
    {
        $trip = Trip::factory()->create(['deliveryID' => null]);
        $vehicle = Vehicle::factory()->create(['companyID' => $this->logisticCompany->companyID]);
        
        $response = $this->postJson('/api/deliveries/assign-trip', [
            'tripID' => $trip->tripID,
            'vehicleID' => $vehicle->vehicleID,
            'scheduled_date' => now()->format('Y-m-d')
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('trips', [
            'tripID' => $trip->tripID,
            'deliveryID' => $response->json('deliveryID')
        ]);
    }
    
    public function test_get_vehicles_returns_available_vehicles()
    {
        Vehicle::factory()->count(3)->create(['companyID' => $this->logisticCompany->companyID]);
        
        $response = $this->getJson('/api/deliveries/vehicles?date=' . now()->format('Y-m-d'));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data'
        ]);
    }
    
    public function test_get_drivers_returns_available_drivers()
    {
        User::factory()->count(3)->create([
            'companyID' => $this->logisticCompany->companyID,
            'role' => 'driver'
        ]);
        
        $response = $this->getJson('/api/deliveries/drivers?date=' . now()->format('Y-m-d'));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data'
        ]);
    }
    
    public function test_create_delivery_creates_new_delivery()
    {
        $vehicle = Vehicle::factory()->create(['companyID' => $this->logisticCompany->companyID]);
        $driver = User::factory()->create([
            'companyID' => $this->logisticCompany->companyID,
            'role' => 'driver'
        ]);
        
        $response = $this->postJson('/api/deliveries/create', [
            'vehicleID' => $vehicle->vehicleID,
            'userID' => $driver->userID,
            'scheduled_date' => now()->format('Y-m-d')
        ]);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('deliveries', [
            'vehicleID' => $vehicle->vehicleID,
            'userID' => $driver->userID
        ]);
    }
    
    public function test_create_trips_creates_trips_for_delivery()
    {
        $delivery = Delivery::factory()->create();
        $order = Order::factory()->create(['order_status' => 'paid']);
        $checkpoint1 = Checkpoint::factory()->create(['orderID' => $order->orderID, 'arrange_number' => 1]);
        $checkpoint2 = Checkpoint::factory()->create(['orderID' => $order->orderID, 'arrange_number' => 2]);
        
        $response = $this->postJson('/api/deliveries/' . $delivery->deliveryID . '/trips', [
            'trips' => [
                [
                    'start_checkID' => $checkpoint1->checkID,
                    'end_checkID' => $checkpoint2->checkID,
                    'orderID' => $order->orderID
                ]
            ]
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('trips', [
            'deliveryID' => $delivery->deliveryID,
            'start_checkID' => $checkpoint1->checkID,
            'end_checkID' => $checkpoint2->checkID
        ]);
    }
}