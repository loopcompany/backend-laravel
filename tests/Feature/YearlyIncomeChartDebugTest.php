<?php

namespace Tests\Feature;

use App\Models\Technician;
use App\Models\TechnicianTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class YearlyIncomeChartDebugTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_query_transactions_correctly()
    {
        // Create technician
        $technician = Technician::create([
            'phone' => '09123456789',
            'password' => bcrypt('password'),
            'name' => 'Test',
            'last_name' => 'Technician',
            'wallet' => 0,
        ]);

        // Create a transaction
        $transaction = TechnicianTransaction::create([
            'technician_id' => $technician->id,
            'order_id' => null,
            'price' => 50000,
            'commission' => 10,
            'type' => 1, // واریز
            'status' => 100, // موفق
            'created_at' => '2024-03-21 12:00:00', // 1403/01/01
        ]);

        // Check if transaction was created
        $this->assertDatabaseHas('technician_transactions', [
            'id' => $transaction->id,
            'technician_id' => $technician->id,
            'price' => 50000,
        ]);

        // Try to query with different methods
        $count1 = TechnicianTransaction::where('technician_id', $technician->id)
            ->where('type', 1)
            ->where('status', 100)
            ->count();

        $count2 = TechnicianTransaction::where('technician_id', $technician->id)
            ->whereIn('type', [1, '1'])
            ->whereIn('status', [100, '100'])
            ->count();

        $this->assertEquals(1, $count1, "Query with direct comparison should find 1 transaction");
        $this->assertEquals(1, $count2, "Query with whereIn should find 1 transaction");

        // Check sum
        $sum = TechnicianTransaction::where('technician_id', $technician->id)
            ->whereIn('type', [1, '1'])
            ->whereIn('status', [100, '100'])
            ->sum('price');

        $this->assertEquals(50000, $sum);
    }

    /** @test */
    public function api_returns_correct_structure()
    {
        $technician = Technician::create([
            'phone' => '09123456789',
            'password' => bcrypt('password'),
            'name' => 'Test',
            'last_name' => 'Technician',
            'wallet' => 25000,
        ]);

        Sanctum::actingAs($technician, ['*'], 'technician');

        $response = $this->getJson('/api/technician/transactions/yearly-income-chart?year=1403');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'year',
                    'monthly_data',
                    'yearly_summary',
                    'current_wallet',
                ],
            ]);

        // Should return 12 months
        $this->assertCount(12, $response->json('data.monthly_data'));
    }
}
