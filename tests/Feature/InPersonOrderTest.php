<?php
namespace Tests\Feature;
use App\Jobs\SendProfileCompletionCode;
use App\Models\{Order, Technician, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

class InPersonOrderTest extends TestCase
{
    use RefreshDatabase, MakesPeople;
    private function input(): array
    {
        return ['request_uuid' => (string) Str::uuid(), 'name' => 'مراجعه‌کننده', 'last_name' => 'حضوری', 'phone' => '09121113344',
            'city_code' => '21', 'region_code' => '18', 'category_id' => $this->makeCategory(),
            'items' => [['kind' => 'service', 'description' => 'خدمت در شرکت', 'unit_price' => 10000000, 'quantity' => 1]],
            'receipts' => [['request_uuid' => (string) Str::uuid(), 'method' => 'cash', 'amount' => 10000000, 'collection_target' => 'technician']]];
    }
    public function test_internal_walk_in_registration_is_atomic_idempotent_and_queues_one_customer_code(): void
    {
        Queue::fake();
        $tech = $this->makeTechnician(['technician_type' => Technician::TYPE_INTERNAL]); Sanctum::actingAs($tech);
        $input = $this->input();
        $one = $this->postJson('/api/technician/in-person-orders', $input)->assertCreated();
        $two = $this->postJson('/api/technician/in-person-orders', $input)->assertCreated();
        $this->assertSame($one->json('order_id'), $two->json('order_id'));
        $this->assertDatabaseCount('orders', 1); $this->assertDatabaseCount('order_receipts', 1);
        $order = Order::firstOrFail();
        $this->assertNotNull($order->settled_at);
        $this->assertMatchesRegularExpression('/^[0-9]{16}$/D', $order->user->code);
        $this->assertSame(4, $order->user->registration_method);
        $this->assertSame($tech->id, $order->registered_by_technician_id);
        $this->assertSame(10000000, $order->in_person_items[0]['unit_price']);
    }
    public function test_field_technician_cannot_create_walk_in_customers_or_bills(): void
    {
        Sanctum::actingAs($this->makeTechnician(['technician_type' => Technician::TYPE_FIELD]));
        $this->postJson('/api/technician/in-person-orders', $this->input())->assertForbidden();
        $this->assertDatabaseCount('orders', 0);
    }
    public function test_existing_customer_identity_is_never_overwritten_and_failed_payment_rolls_back_new_customer(): void
    {
        Queue::fake(); Sanctum::actingAs($this->makeTechnician(['technician_type' => Technician::TYPE_INTERNAL]));
        $existing = $this->makeUser(['phone' => '09121113344', 'name' => 'نام اصلی']);
        $input = $this->input();
        $this->postJson('/api/technician/in-person-orders', $input)->assertCreated();
        $this->assertSame('نام اصلی', $existing->fresh()->name);
        $input['request_uuid'] = (string) Str::uuid(); $input['phone'] = '09121113355'; $input['receipts'][0]['amount'] = 20000000;
        $this->postJson('/api/technician/in-person-orders', $input)->assertUnprocessable();
        $this->assertFalse(User::where('phone', '09121113355')->exists());
        $this->assertDatabaseCount('orders', 1);
    }
}
