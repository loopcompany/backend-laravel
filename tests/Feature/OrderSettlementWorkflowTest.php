<?php
namespace Tests\Feature;

use App\Models\{Admin, OrderReceipt, ReferralCommission, Technician, UserTransaction};
use App\Services\{OrderSettlementService, ReferralAgentService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

class OrderSettlementWorkflowTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    private function admin(): Admin
    {
        $admin = Admin::create(['name' => 'حسابداری', 'email' => Str::uuid().'@test.local', 'password' => 'secret', 'is_active' => true]);
        foreach (['review-order-settlements', 'receive-technician-custody'] as $name) {
            $admin->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']));
        }
        return $admin;
    }

    private function receipt(int $amount, string $method = 'cash'): array
    {
        return ['request_uuid' => (string) Str::uuid(), 'amount' => $amount, 'method' => $method,
            'collection_target' => 'technician', 'item_description' => $method === 'barter' ? 'مانیتور ۲۵ اینچ' : null];
    }

    public function test_field_mixed_receipts_wait_for_admin_and_custody_survives_payment_approval(): void
    {
        $tech = $this->makeTechnician(['technician_type' => Technician::TYPE_FIELD]);
        $order = $this->makeOrder($this->makeUser(), ['technician_id' => $tech->id, 'technician_price' => 10000000]);
        $service = app(OrderSettlementService::class); $admin = $this->admin();
        $one = $service->record($order, $tech, $this->receipt(2000000));
        $two = $service->record($order, $tech, $this->receipt(4000000, 'card_to_card'));
        $three = $service->record($order, $tech, $this->receipt(4000000, 'barter'));
        $this->assertSame(0, $service->paid($order));
        $this->assertNull($order->fresh()->settled_at);
        foreach ([$one, $two, $three] as $receipt) $service->review($receipt, true, null, $admin);
        $this->assertSame(10000000, $service->paid($order));
        $this->assertNotNull($order->fresh()->settled_at);
        $this->assertSame('held', $three->fresh()->custody_status);
        $service->returnCustody($three, $admin);
        $this->assertSame('returned', $three->fresh()->custody_status);
        $this->assertSame(3, UserTransaction::where('order_id', $order->id)->count());
    }

    public function test_internal_receipts_are_approved_immediately_and_retries_do_not_credit_twice(): void
    {
        $tech = $this->makeTechnician(['technician_type' => Technician::TYPE_INTERNAL]);
        $order = $this->makeOrder($this->makeUser(), ['technician_id' => $tech->id, 'technician_price' => 10000000]);
        $service = app(OrderSettlementService::class); $data = $this->receipt(10000000);
        $one = $service->record($order, $tech, $data); $two = $service->record($order, $tech, $data);
        $this->assertSame($one->id, $two->id);
        $this->assertSame('approved', $one->status);
        $this->assertNotNull($order->fresh()->settled_at);
        $this->assertSame(1, UserTransaction::where('order_id', $order->id)->count());
        $this->expectException(ValidationException::class);
        $service->record($order, $tech, array_replace($data, ['amount' => 2000000]));
    }

    public function test_pending_receipts_reserve_amount_and_rejection_releases_it_but_keeps_custody(): void
    {
        $tech = $this->makeTechnician(['technician_type' => Technician::TYPE_FIELD]);
        $order = $this->makeOrder($this->makeUser(), ['technician_id' => $tech->id, 'technician_price' => 10000000]);
        $service = app(OrderSettlementService::class); $receipt = $service->record($order, $tech, $this->receipt(7000000));
        $this->assertSame(3000000, $service->summary($order)['recordable']);
        $service->review($receipt, false, 'مبلغ یا تصویر رسید صحیح نیست', $this->admin());
        $this->assertSame(10000000, $service->summary($order)['recordable']);
        $this->assertSame('held', $receipt->fresh()->custody_status);
    }

    public function test_online_full_payment_settles_and_releases_owner_commission_without_any_code(): void
    {
        $user = $this->makeUser(['is_referral_agent' => true]);
        $order = $this->makeOrder($user, ['technician_price' => 10000000]);
        $commission = ReferralCommission::where('order_id', $order->id)->firstOrFail();
        $this->assertNull($commission->referral_code_id);
        $this->assertSame('pending', $commission->status);
        UserTransaction::create(['order_id' => $order->id, 'user_id' => $user->id, 'price' => 10000000, 'type' => 2, 'status' => 100, 'payment_method' => 'in_app']);
        $order->update(['payment_status' => 1]);
        $this->assertNotNull($order->fresh()->settled_at);
        $this->assertSame('pending', $commission->fresh()->status);
        $admin = $this->admin();
        $admin->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'edit-referral-withdrawals', 'guard_name' => 'admin']));
        app(ReferralAgentService::class)->approveForWithdrawal($commission, $admin);
        $this->assertSame('available', $commission->fresh()->status);
        $this->assertSame($admin->id, (int) $commission->fresh()->approved_by);
        $this->assertGreaterThan(0, app(ReferralAgentService::class)->summary($user)['available_balance']);
    }

    public function test_another_technician_and_customer_cannot_record_or_view_the_receipts(): void
    {
        $tech = $this->makeTechnician();
        $order = $this->makeOrder($this->makeUser(), ['technician_id' => $tech->id, 'technician_price' => 10000000]);
        Sanctum::actingAs($this->makeTechnician());
        $this->getJson('/api/technician/orders/'.$order->id.'/settlement')->assertForbidden();
        $this->postJson('/api/technician/orders/'.$order->id.'/settlement/receipts', $this->receipt(1000000))->assertForbidden();
        Sanctum::actingAs($order->user);
        $this->getJson('/api/technician/orders/'.$order->id.'/settlement')->assertForbidden();
    }

    public function test_partial_offline_payment_reduces_the_online_balance_without_changing_bill_total(): void
    {
        $tech = $this->makeTechnician(['technician_type' => Technician::TYPE_INTERNAL]);
        $order = $this->makeOrder($this->makeUser(), ['technician_id' => $tech->id, 'technician_price' => 10000000]);
        $service = app(OrderSettlementService::class);
        $service->record($order, $tech, $this->receipt(2000000));
        $this->assertSame(10000000, $service->total($order));
        $this->assertEquals(8000000, $order->payment_price());
        $this->assertNull($order->fresh()->settled_at);
    }
}
