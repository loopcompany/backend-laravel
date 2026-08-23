<?php

namespace Tests\Feature;

use App\Models\Technician;
use App\Models\TechnicianTransaction;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TechnicianYearlyIncomeChartTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_yearly_income_chart_with_correct_structure()
    {
        // Arrange: Create technician
        $technician = Technician::factory()->create([
            'wallet' => 50000,
        ]);

        // Create some transactions for different months
        $order = Order::factory()->create();
        
        // فروردین 1403
        TechnicianTransaction::create([
            'technician_id' => $technician->id,
            'order_id' => $order->id,
            'price' => 10000,
            'commission' => 10,
            'type' => 1, // واریز از سفارش
            'status' => 100, // موفق
            'created_at' => '2024-03-21', // 1403/01/01
        ]);

        // اردیبهشت 1403
        TechnicianTransaction::create([
            'technician_id' => $technician->id,
            'order_id' => $order->id,
            'price' => 15000,
            'commission' => 10,
            'type' => 1,
            'status' => 100,
            'created_at' => '2024-04-25', // 1403/02/06
        ]);

        // Authenticate
        Sanctum::actingAs($technician, ['*'], 'technician');

        // Act
        $response = $this->getJson('/api/technician/transactions/yearly-income-chart?year=1403');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'year',
                    'year_label',
                    'monthly_data' => [
                        '*' => [
                            'month',
                            'month_name',
                            'total_income',
                            'total_settlements',
                            'net_income',
                        ]
                    ],
                    'yearly_summary' => [
                        'total_income',
                        'total_settlements',
                        'net_income',
                    ],
                    'current_wallet',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'year' => 1403,
                ],
            ]);

        // Check that we have 12 months
        $this->assertCount(12, $response->json('data.monthly_data'));

        // Check specific months have correct income
        $monthlyData = $response->json('data.monthly_data');
        
        // فروردین (month 1) should have 10000
        $farvardin = collect($monthlyData)->firstWhere('month', 1);
        $this->assertEquals(10000, $farvardin['total_income']);

        // اردیبهشت (month 2) should have 15000
        $ordibehesht = collect($monthlyData)->firstWhere('month', 2);
        $this->assertEquals(15000, $ordibehesht['total_income']);

        // Yearly total should be 25000
        $this->assertEquals(25000, $response->json('data.yearly_summary.total_income'));
    }

    /** @test */
    public function it_filters_only_successful_transactions()
    {
        $technician = Technician::factory()->create();
        $order = Order::factory()->create();

        // موفق
        TechnicianTransaction::create([
            'technician_id' => $technician->id,
            'order_id' => $order->id,
            'price' => 10000,
            'commission' => 10,
            'type' => 1,
            'status' => 100, // موفق
            'created_at' => '2024-03-21',
        ]);

        // ناموفق - نباید شمرده بشه
        TechnicianTransaction::create([
            'technician_id' => $technician->id,
            'order_id' => $order->id,
            'price' => 5000,
            'commission' => 10,
            'type' => 1,
            'status' => -200, // ناموفق
            'created_at' => '2024-03-22',
        ]);

        Sanctum::actingAs($technician, ['*'], 'technician');

        $response = $this->getJson('/api/technician/transactions/yearly-income-chart?year=1403');

        $monthlyData = $response->json('data.monthly_data');
        $farvardin = collect($monthlyData)->firstWhere('month', 1);

        // فقط تراکنش موفق باید شمرده بشه
        $this->assertEquals(10000, $farvardin['total_income']);
    }

    /** @test */
    public function it_only_includes_type_1_transactions()
    {
        $technician = Technician::factory()->create();
        $order = Order::factory()->create();

        // Type 1 - واریز از سفارش
        TechnicianTransaction::create([
            'technician_id' => $technician->id,
            'order_id' => $order->id,
            'price' => 10000,
            'commission' => 10,
            'type' => 1,
            'status' => 100,
            'created_at' => '2024-03-21',
        ]);

        // Type 2 - برداشت - نباید شمرده بشه
        TechnicianTransaction::create([
            'technician_id' => $technician->id,
            'order_id' => $order->id,
            'price' => 3000,
            'commission' => 10,
            'type' => 2,
            'status' => 100,
            'created_at' => '2024-03-22',
        ]);

        Sanctum::actingAs($technician, ['*'], 'technician');

        $response = $this->getJson('/api/technician/transactions/yearly-income-chart?year=1403');

        $monthlyData = $response->json('data.monthly_data');
        $farvardin = collect($monthlyData)->firstWhere('month', 1);

        // فقط type 1 باید شمرده بشه
        $this->assertEquals(10000, $farvardin['total_income']);
    }
}
