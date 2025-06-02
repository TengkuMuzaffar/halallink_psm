<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Delivery;
use App\Models\Trip;
use App\Models\Checkpoint;
use App\Models\Verify;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExecuteDeliveriesControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $driverUser;
    protected $delivery;
    
    protected function setUp(): void
    {
        parent::setUp();
        $company = Company::factory()->create(['company_type' => 'logistic']);
        $this->driverUser = User::factory()->create([
            'companyID' => $company->companyID,
            'role' => 'driver'
        ]);
        $this->delivery = Delivery::factory()->create([
            'userID' => $this->driverUser->userID
        ]);
        $this->actingAs($this->driverUser);
    }
    
    public function test_index_returns_deliveries_for_driver()
    {
        $checkpoint1 = Checkpoint::factory()->create();
        $checkpoint2 = Checkpoint::factory()->create();
        Trip::factory()->create([
            'deliveryID' => $this->delivery->deliveryID,
            'start_checkID' => $checkpoint1->checkID,
            'end_checkID' => $checkpoint2->checkID
        ]);
        
        $response = $this->getJson('/api/execute-deliveries');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data'
        ]);
    }
    
    public function test_start_delivery_updates_start_timestamp()
    {
        $response = $this->postJson('/api/execute-deliveries/' . $this->delivery->deliveryID . '/start');
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('deliveries', [
            'deliveryID' => $this->delivery->deliveryID,
            'start_timestamp' => now()->toDateTimeString()
        ]);
    }
    
    public function test_complete_delivery_updates_end_timestamp()
    {
        // First start the delivery
        $this->delivery->update(['start_timestamp' => now()]);
        
        // Create verifications and mark them as complete
        $checkpoint1 = Checkpoint::factory()->create();
        $checkpoint2 = Checkpoint::factory()->create();
        Trip::factory()->create([
            'deliveryID' => $this->delivery->deliveryID,
            'start_checkID' => $checkpoint1->checkID,
            'end_checkID' => $checkpoint2->checkID
        ]);
        
        Verify::factory()->create([
            'deliveryID' => $this->delivery->deliveryID,
            'checkID' => $checkpoint1->checkID,
            'verify_status' => 'complete'
        ]);
        
        Verify::factory()->create([
            'deliveryID' => $this->delivery->deliveryID,
            'checkID' => $checkpoint2->checkID,
            'verify_status' => 'complete'
        ]);
        
        $response = $this->postJson('/api/execute-deliveries/' . $this->delivery->deliveryID . '/complete');
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('deliveries', [
            'deliveryID' => $this->delivery->deliveryID,
            'end_timestamp' => now()->toDateTimeString()
        ]);
    }
    
    public function test_get_delivery_details_returns_delivery_information()
    {
        $checkpoint1 = Checkpoint::factory()->create();
        $checkpoint2 = Checkpoint::factory()->create();
        Trip::factory()->create([
            'deliveryID' => $this->delivery->deliveryID,
            'start_checkID' => $checkpoint1->checkID,
            'end_checkID' => $checkpoint2->checkID
        ]);
        
        $response = $this->getJson('/api/execute-deliveries/' . $this->delivery->deliveryID);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'delivery',
            'trips',
            'checkpoints',
            'verifications'
        ]);
    }
}