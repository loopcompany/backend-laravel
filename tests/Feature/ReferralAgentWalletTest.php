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
        $code = app(ReferralAgentService::class)->assignAgent($agent, 10, 5);

        return [$agent->fresh(), $code];
    }

    private function submit(User $buyer, string $code, int $price = 1000000)
    {
        Sanctum::actingAs($buyer);
        $addressId = DB::table('user_addresses')->insertGetId([
            'user_id' => $buyer->id, 'title' => 'خانه', 'fname' => 'a', 'lname' => 'b', 'mobile' => '09120000000',
            'city' => 'تهران', 'region' => '1', 'address' => 'x', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $this->postJson('/api/orders/submit', [
            'address_id' => $addressId, 'category_id' => $this->makeCategory(), 'total_price' => $price,
            'date' => now()->addDay()->format('Y-m-d'), 'time' => '10 - 12', 'platform' => 'android', 'referral_code' => $code,
        ]);
    }

    public function test_summary_for_non_agent(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->getJson('/api/referral-agent/summary')->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_agent', false);
        $this->getJson('/api/referral-agent/commissions')->assertForbidden();
    }

    public function test_agent_code_is_multi_use_and_commission_follows_order_status(): void
    {
        [$agent, $code] = $this->agent();
        $this->assertTrue($code->multi_use);
        $this->assertMatchesRegularExpression('/^LOOP-[A-Z0-9]{6}$/', $code->code);

        $first = $this->submit($this->makeUser(), $code->code)->assertCreated()->json('data.order_id');
        $second = $this->submit($this->makeUser(), $code->code)->assertCreated()->json('data.order_id');
        $canceled = $this->submit($this->makeUser(), $code->code)->assertCreated()->json('data.order_id');

        // صاحب کد نمی‌تواند از کد خودش استفاده کند
        $this->submit($agent, $code->code)->assertStatus(409);
        $this->assertSame('active', $code->fresh()->status);

        // هر سفارش: مبنا = ۱,۰۰۰,۰۰۰ − ۵٪ تخفیف = ۹۵۰,۰۰۰ ← پورسانت ۱۰٪ = ۹۵,۰۰۰
        $this->assertSame(3, ReferralCommission::where('status', 'pending')->count());
        $this->assertSame(95000, ReferralCommission::where('order_id', $first)->value('commission_amount'));

        \App\Models\Order::find($first)->update(['payment_status' => 1, 'status' => 2]);
        \App\Models\Order::whereKey($canceled)->update(['status' => 3]); // query builder: بدون رویداد مدل

        Sanctum::actingAs($agent);
        $this->getJson('/api/referral-agent/summary')->assertOk()
            ->assertJsonPath('data.is_agent', true)
            ->assertJsonPath('data.referral_code', $code->code)
            ->assertJsonPath('data.commission_percent', 10)
            ->assertJsonPath('data.available_balance', 95000)
            ->assertJsonPath('data.pending_balance', 95000)
            ->assertJsonPath('data.total_earned', 95000)
            ->assertJsonPath('data.min_withdraw_amount', 500000);

        $statuses = collect($this->getJson('/api/referral-agent/commissions')->assertOk()->json('data'))->pluck('status', 'order_id');
        $this->assertSame('available', $statuses[$first]);
        $this->assertSame('pending', $statuses[$second]);
        $this->assertSame('canceled', $statuses[$canceled]);
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
