<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\ReferralCommission;
use App\Models\ReferralWithdrawal;
use App\Models\User;
use App\Services\ReferralAgentService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesPeople;
use Tests\Fakes\FakeSmsService;
use Tests\TestCase;

/**
 * سند اپ کاربر، بخش ۱۳: کیف پول مأمور خرید (پورسانت، برداشت به شبا، تهاتر).
 */
class ReferralAgentWalletTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    /** شبای معتبر با checksum درست */
    private const SHEBA = 'IR062960000000100324200001';

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(SmsService::class, new FakeSmsService());
    }

    private function agent(): array
    {
        $agent = $this->makeUser(['account_type' => 'company']);
        app(ReferralAgentService::class)->setAgent($agent, true);
        return [$agent->fresh()];
    }

    public function test_summary_for_non_agent(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->getJson('/api/referral-agent/summary')->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_agent', false);
        $this->getJson('/api/referral-agent/commissions')->assertForbidden();
    }

    public function test_commission_follows_the_buyers_flag_and_audited_settlement(): void
    {
        [$agent] = $this->agent();
        $paid = $this->makeOrder($agent, ['technician_price' => 700000]);
        $pending = $this->makeOrder($agent, ['technician_price' => 700000]);
        $canceled = $this->makeOrder($agent, ['technician_price' => 700000]);
        $this->makeOrder($this->makeUser(), ['technician_price' => 700000]);
        $paid->updateQuietly(['settled_at' => now(), 'payment_status' => 1]);
        $admin = \App\Models\Admin::create(['name' => 'حسابدار', 'email' => 'commission@test.local', 'password' => 'secret', 'is_active' => true]);
        $admin->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'edit-referral-withdrawals', 'guard_name' => 'admin']));
        app(ReferralAgentService::class)->approveForWithdrawal(ReferralCommission::where('order_id', $paid->id)->firstOrFail(), $admin);
        \App\Models\Order::whereKey($canceled->id)->update(['status' => 3]);

        Sanctum::actingAs($agent);
        $this->getJson('/api/referral-agent/summary')->assertOk()
            ->assertJsonPath('data.is_agent', true)
            ->assertJsonMissingPath('data.referral_code')
            ->assertJsonPath('data.available_balance', 100000)
            ->assertJsonPath('data.pending_balance', 100000);
        $statuses = collect($this->getJson('/api/referral-agent/commissions')->assertOk()->json('data'))->pluck('status', 'order_id');
        $this->assertSame('available', $statuses[$paid->id]);
        $this->assertSame('pending', $statuses[$pending->id]);
        $this->assertSame('canceled', $statuses[$canceled->id]);
        $this->assertCount(3, $statuses);
    }

    public function test_offset_and_sheba_withdrawals(): void
    {
        [$agent] = $this->agent();
        AppSetting::put(AppSetting::REFERRAL_MIN_WITHDRAW_AMOUNT, 50000);
        $order = $this->makeOrder($this->makeUser(), ['status' => 2, 'payment_status' => 1]);
        ReferralCommission::create([
            'user_id' => $agent->id, 'order_id' => $order->id, 'order_amount' => 2000000,
            'commission_percent' => 10, 'commission_amount' => 200000, 'status' => 'available',
        ]);
        Sanctum::actingAs($agent);

        $this->postJson('/api/referral-agent/withdrawals', ['amount' => 300000, 'method' => 'offset'])
            ->assertStatus(422)->assertJsonPath('error_code', 'INSUFFICIENT_BALANCE');

        // تهاتر: فوری به کیف پول لوپ
        $this->postJson('/api/referral-agent/withdrawals', ['amount' => 50000, 'method' => 'offset'])
            ->assertCreated()->assertJsonPath('data.status', 'done');
        $this->assertSame(50000, (int) $agent->fresh()->wallet);
        $this->assertDatabaseHas('user_transactions', ['user_id' => $agent->id, 'type' => 5, 'price' => 50000, 'description' => 'تهاتر پورسانت']);

        $this->postJson('/api/referral-agent/withdrawals', ['amount' => 10000, 'method' => 'sheba', 'sheba_number' => self::SHEBA])
            ->assertStatus(422)->assertJsonPath('error_code', 'BELOW_MINIMUM');
        $this->postJson('/api/referral-agent/withdrawals', ['amount' => 60000, 'method' => 'sheba', 'sheba_number' => 'IR062960000000100324200002'])
            ->assertStatus(422)->assertJsonPath('error_code', 'INVALID_SHEBA');

        $withdrawal = $this->postJson('/api/referral-agent/withdrawals', ['amount' => 100000, 'method' => 'sheba', 'sheba_number' => self::SHEBA])
            ->assertCreated()->assertJsonPath('data.status', 'pending')->json('data');

        // مبلغ رزرو شده است
        $this->getJson('/api/referral-agent/summary')
            ->assertJsonPath('data.available_balance', 50000)
            ->assertJsonPath('data.total_offset', 50000);

        // رد پنل ← مبلغ به موجودی برمی‌گردد
        ReferralWithdrawal::find($withdrawal['id'])->update(['status' => 'rejected', 'admin_note' => 'شبا به نام شما نیست']);
        $this->getJson('/api/referral-agent/summary')->assertJsonPath('data.available_balance', 150000);

        $this->getJson('/api/referral-agent/withdrawals')->assertOk()
            ->assertJsonPath('data.0.status', 'rejected')
            ->assertJsonPath('data.0.admin_note', 'شبا به نام شما نیست');
    }

    public function test_sheba_checksum(): void
    {
        $this->assertTrue(ReferralAgentService::isValidSheba(self::SHEBA));
        $this->assertFalse(ReferralAgentService::isValidSheba('IR062960000000100324200002'));
        $this->assertSame(self::SHEBA, ReferralAgentService::normalizeSheba('062960000000100324200001'));
    }
}
