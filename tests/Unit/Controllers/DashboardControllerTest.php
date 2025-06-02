<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Delivery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $adminUser;
    
    protected function setUp(): void
    {
        parent::setUp();
        $adminCompany = Company::factory()->create(['company_type' => 'admin']);
        $this->adminUser = User::factory()->create([
            'companyID' => $adminCompany->companyID,
            'role' => 'admin'
        ]);
        $this->actingAs($this->adminUser);
    }
    
    public function test_get_stats_returns_company_counts_by_type()
    {
        // Create companies of different types with admin users
        $broilerCompany = Company::factory()->create(['company_type' => 'broiler']);
        User::factory()->create(['companyID' => $broilerCompany->companyID, 'role' => 'admin']);
        
        $slaughterhouseCompany = Company::factory()->create(['company_type' => 'slaughterhouse']);
        User::factory()->create(['companyID' => $slaughterhouseCompany->companyID, 'role' => 'admin']);
        
        $smeCompany = Company::factory()->create(['company_type' => 'sme']);
        User::factory()->create(['companyID' => $smeCompany->companyID, 'role' => 'admin']);
        
        $response = $this->getJson('/api/dashboard/stats');
        
        $response->assertStatus(200);
        $response->assertJson([
            'broiler' => 1,
            'slaughterhouse' => 1,
            'sme' => 1,
            'logistic' => 0
        ]);
    }
    
    public function test_get_top_performers_returns_company_performance_metrics()
    {
        // Create test data for companies, orders, deliveries, etc.
        
        $response = $this->getJson('/api/dashboard/top-performers');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'order_volume',
            'delivery_excellence',
            'quality_assurance',
            'financial_performance',
            'certification_leaders'
        ]);
    }
    
    public function test_get_recent_activities_returns_latest_transactions()
    {
        // Create test orders, payments, deliveries
        Order::factory()->count(5)->create(['order_status' => 'paid']);
        
        $response = $this->getJson('/api/dashboard/recent-activities');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'recent_orders',
            'recent_payments',
            'recent_deliveries'
        ]);
    }
    
    public function test_get_sales_data_returns_sales_statistics()
    {
        // Create test payment data
        Payment::factory()->count(10)->create(['payment_status' => 'completed']);
        
        $response = $this->getJson('/api/dashboard/sales-data');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'daily',
            'weekly',
            'monthly',
            'yearly'
        ]);
    }
}