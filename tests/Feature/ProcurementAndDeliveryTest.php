<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Order;
use App\Models\OrderProcurement;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserTransaction;
use App\Services\ProcurementService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesPeople;
use Tests\Fakes\FakeSmsService;
use Tests\TestCase;

/**
 * سند اپ کاربر، بخش ۹ (تامین کالا)، ۹-۵ (پیش‌پرداخت اعزام به لوپ) و ۱۰ (مشخصات تحویل).
 */
class ProcurementAndDeliveryTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(SmsService::class, new FakeSmsService());
    }

    private function companyUser(array $overrides = []): User
    {
        $user = $this->makeUser(['account_type' => 'company'] + $overrides);
        Organization::factory()->create(['user_id' => $user->id, 'profile_status' => 'approved', 'contract_status' => 'approved']);

        return $user->fresh();
    }

    private function submitPayload(User $user, array $extra = []): array
    {
        $addressId = DB::table('user_addresses')->insertGetId([
            'user_id' => $user->id, 'title' => 'دفتر', 'fname' => 'علی', 'lname' => 'رضایی', 'mobile' => '09120000000',
            'city' => 'تهران', 'region' => '1', 'address' => 'خیابان آزادی', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return array_merge([
            'address_id' => $addressId,
            'category_id' => $this->makeCategory(),
            'total_price' => 0,
            'date' => now()->addDay()->format('Y-m-d'),
            'time' => '10 - 12',
            'platform' => 'android',
        ], $extra);
    }

    private function procurementItems(): array
    {
        return [
            ['item_id' => 'laptop', 'title' => 'لپ تاپ', 'new_count' => 2, 'used_count' => 1, 'description' => 'i7'],
            ['item_id' => 'printer', 'title' => 'پرینتر', 'new_count' => 0, 'used_count' => 0],
        ];
    }

    /** سفارش + اعلام قیمت پنل: ۲ لپ‌تاپ نو × ۴۵م + ۱ کارکرده × ۲۰م = ۱۱۰م */
    private function quotedOrder(User $user): Order
    {
        Sanctum::actingAs($user);
        $orderId = $this->postJson('/api/orders/submit', $this->submitPayload($user, [
            'procurement_items' => $this->procurementItems(),
            'delivery_mode' => ['once_short'],
            'technician_gender' => 'male',
        ]))->assertCreated()->json('data.order_id');

        $procurement = OrderProcurement::where('order_id', $orderId)->firstOrFail();
        foreach ($procurement->items as $item) {
            $item->update(['unit_price' => $item->condition === 'new' ? 45000000 : 20000000]);
        }
        app(ProcurementService::class)->quote($procurement->fresh());

        return Order::findOrFail($orderId);
    }

    // ----------------------------------------------------------------- ۹-۱ ثبت سفارش

    public function test_submit_stores_procurement_fields_and_creates_quote_request_for_company(): void
    {
        $user = $this->companyUser();
        Sanctum::actingAs($user);

        $orderId = $this->postJson('/api/orders/submit', $this->submitPayload($user, [
            'procurement_items' => $this->procurementItems(),
            'delivery_mode' => ['once_short', 'project'],
            'technician_gender' => 'male',
        ]))->assertCreated()->json('data.order_id');

        $order = Order::findOrFail($orderId);
        $this->assertSame(['once_short', 'project'], $order->delivery_mode);
        $this->assertSame('male', $order->technician_gender);
        $this->assertCount(2, $order->procurement_items);

        $procurement = $order->procurement;
        $this->assertSame(OrderProcurement::STATUS_AWAITING_QUOTE, $procurement->status);
        $this->assertSame(60, $procurement->prepayment_percent);
        // قلم بدون تعداد حذف و هر وضعیت (نو / کارکرده) یک ردیف جداست
        $this->assertSame([['new', 2], ['used', 1]], $procurement->items->map(fn ($i) => [$i->condition, $i->quantity])->all());

        $this->postJson('/api/orders/detail', ['orderId' => $orderId])->assertOk()
            ->assertJsonPath('procurement.status', 'awaiting_quote')
            ->assertJsonPath('procurement.items.0.title', 'لپ تاپ')
            ->assertJsonPath('procurement.items.0.unit_price', null)
            ->assertJsonPath('delivery.status', 'pending');

        $list = collect($this->getJson('/api/orders')->assertOk()->json('data'))->firstWhere('id', $orderId);
        $this->assertSame('awaiting_quote', $list['procurement']['status']);
    }

    public function test_individual_user_gets_no_quote_request_and_invalid_mode_is_rejected(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/orders/submit', $this->submitPayload($user, ['delivery_mode' => ['weekly']]))
            ->assertStatus(422);

        $orderId = $this->postJson('/api/orders/submit', $this->submitPayload($user, ['procurement_items' => $this->procurementItems()]))
            ->assertCreated()->json('data.order_id');

        $this->assertNull(Order::find($orderId)->procurement);
        $this->postJson('/api/orders/detail', ['orderId' => $orderId])->assertOk()
            ->assertJsonPath('procurement', null)
            ->assertJsonPath('delivery', null);
    }

    // ----------------------------------------------------------------- ۹-۲ تا ۹-۴

    public function test_quote_is_valid_until_end_of_business_day(): void
    {
        AppSetting::put(AppSetting::BUSINESS_END_TIME, '17:00');

        Carbon::setTestNow('2026-10-12 10:20:00');
        $this->assertSame('2026-10-12 17:00', ProcurementService::quoteValidUntil()->format('Y-m-d H:i'));

        // بعد از ساعت کاری، اعتبار تا پایان روز کاری بعد
        Carbon::setTestNow('2026-10-12 18:00:00');
        $this->assertSame('2026-10-13 17:00', ProcurementService::quoteValidUntil()->format('Y-m-d H:i'));
        Carbon::setTestNow();
    }

    public function test_quote_requires_unit_price_for_every_item(): void
    {
        $user = $this->companyUser();
        Sanctum::actingAs($user);
        $orderId = $this->postJson('/api/orders/submit', $this->submitPayload($user, ['procurement_items' => $this->procurementItems()]))
            ->json('data.order_id');

        $this->expectExceptionMessage('برای همه‌ی اقلام قیمت واحد وارد کنید.');
        app(ProcurementService::class)->quote(Order::find($orderId)->procurement);
    }

    public function test_full_flow_accept_prepay_ready_remaining_ship_and_receive(): void
    {
        $user = $this->companyUser(['wallet' => 200000000]);
        $order = $this->quotedOrder($user);

        $this->postJson('/api/orders/detail', ['orderId' => $order->id])->assertOk()
            ->assertJsonPath('procurement.status', 'quoted')
            ->assertJsonPath('procurement.total_amount', 110000000)
            ->assertJsonPath('procurement.payment.prepayment_percent', 60)
            ->assertJsonPath('procurement.payment.prepayment_amount', 66000000)
            ->assertJsonPath('procurement.payment.remaining_amount', 44000000)
            ->assertJsonPath('procurement.payment.prepayment_paid', false);

        // پیش‌پرداخت قبل از تأیید ممکن نیست
        $this->postJson("/api/orders/{$order->id}/procurement/pay", ['stage' => 'prepayment', 'method' => 'wallet'])
            ->assertStatus(422)->assertJsonPath('error_code', 'INVALID_STAGE');

        $this->postJson("/api/orders/{$order->id}/procurement/accept")->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $this->postJson("/api/orders/{$order->id}/procurement/pay", ['stage' => 'prepayment', 'method' => 'wallet'])
            ->assertOk()->assertJsonPath('data.remaining_balance', 134000000);
        $this->assertSame('preparing', $order->procurement->fresh()->status);
        $this->assertDatabaseHas('user_transactions', [
            'user_id' => $user->id, 'order_id' => $order->id, 'type' => 3, 'price' => 66000000, 'payment_method' => 'in_app',
        ]);

        // باقی‌مانده فقط وقتی کالا آماده است
        $this->postJson("/api/orders/{$order->id}/procurement/pay", ['stage' => 'remaining', 'method' => 'wallet'])
            ->assertStatus(422);

        $service = app(ProcurementService::class);
        $service->markReady($order->procurement->fresh());

        // ارسال پیش از پرداخت باقی‌مانده ممکن نیست
        try {
            $service->markShipped($order->procurement->fresh());
            $this->fail('shipping before remaining payment must fail');
        } catch (\App\Exceptions\BusinessRuleException $e) {
            $this->assertSame('REMAINING_NOT_PAID', $e->errorCode);
        }

        $this->postJson("/api/orders/{$order->id}/procurement/pay", ['stage' => 'remaining', 'method' => 'wallet'])->assertOk();
        // سفارش فقط تامین کالا ← تسویه‌ی کامل
        $this->assertSame(1, (int) $order->fresh()->payment_status);

        $this->putJson("/api/orders/{$order->id}/delivery", [
            'receiver_type' => 'representative', 'method' => 'courier',
            'receiver_name' => 'علی رضایی', 'receiver_mobile' => '09121112233', 'receiver_national_id' => '0012345678',
        ])->assertOk()->assertJsonPath('data.receiver_name', 'علی رضایی');

        $service->markShipped($order->procurement->fresh());

        $this->putJson("/api/orders/{$order->id}/delivery", ['receiver_type' => 'self', 'method' => 'user'])
            ->assertStatus(422)->assertJsonPath('error_code', 'DELIVERY_LOCKED');

        // «دریافت محصول / سفارش» تاریخ تحویل را ثبت می‌کند
        $this->postJson("/api/orders/{$order->id}/final-description", ['description' => 'دریافت شد'])->assertOk();

        $detail = $this->postJson('/api/orders/detail', ['orderId' => $order->id])->assertOk();
        $detail->assertJsonPath('delivery.status', 'delivered')
            ->assertJsonPath('delivery.method', 'courier')
            ->assertJsonPath('procurement.status', 'delivered');
        $this->assertNotNull($detail->json('delivery.delivered_at'));
    }

    public function test_expired_quote_cannot_be_accepted_and_can_be_requoted(): void
    {
        $user = $this->companyUser();
        $order = $this->quotedOrder($user);

        $this->travelTo($order->procurement->valid_until->copy()->addMinute());

        $this->postJson('/api/orders/detail', ['orderId' => $order->id])->assertJsonPath('procurement.status', 'expired');
        $this->postJson("/api/orders/{$order->id}/procurement/accept")
            ->assertStatus(422)->assertJsonPath('error_code', 'QUOTE_EXPIRED');

        $this->postJson("/api/orders/{$order->id}/procurement/requote")->assertOk()
            ->assertJsonPath('data.status', 'awaiting_quote');
        // در وضعیت منتظر قیمت، استعلام مجدد معنا ندارد
        $this->postJson("/api/orders/{$order->id}/procurement/requote")->assertStatus(422);
    }

    public function test_wallet_shortage_and_reject_and_ownership(): void
    {
        $user = $this->companyUser(['wallet' => 1000]);
        $order = $this->quotedOrder($user);

        $this->postJson("/api/orders/{$order->id}/procurement/accept")->assertOk();
        $this->postJson("/api/orders/{$order->id}/procurement/pay", ['stage' => 'prepayment', 'method' => 'wallet'])
            ->assertStatus(422)->assertJsonPath('error_code', 'INSUFFICIENT_BALANCE');
        $this->assertSame(0, UserTransaction::count());

        $this->postJson("/api/orders/{$order->id}/procurement/reject", ['reason' => 'گران است'])->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        // سفارش کاربر دیگر
        Sanctum::actingAs($this->makeUser());
        $this->postJson("/api/orders/{$order->id}/procurement/accept")->assertNotFound();

        // توکن تکنسین با همان شناسه
        Sanctum::actingAs($this->makeTechnician());
        $this->postJson("/api/orders/{$order->id}/procurement/accept")->assertForbidden();
    }

    // ----------------------------------------------------------------- ۹-۵ اعزام به لوپ

    public function test_loop_dispatch_defaults_to_60_percent_and_accept_requires_prepayment(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user, ['status' => 1]);

        app(\App\Repositories\OrderRepository::class)->updateSendToLoop($order->id);
        $order->refresh();
        $this->assertSame(60, (int) $order->prepayment);

        // درصد ثبت‌شده‌ی پنل تغییر نمی‌کند
        $other = $this->makeOrder($user, ['status' => 1, 'prepayment' => 30]);
        app(\App\Repositories\OrderRepository::class)->updateSendToLoop($other->id);
        $this->assertSame(30, (int) $other->fresh()->prepayment);

        $order->forceFill(['loop_cost_estimate' => 1000000, 'duration' => 3])->save();
        Sanctum::actingAs($user);

        $this->postJson("/api/orders/{$order->id}/decision", ['decision' => 'ok'])
            ->assertStatus(422)->assertJsonPath('error_code', 'PREPAYMENT_REQUIRED');

        $order->forceFill(['prepayment_payment_status' => 1])->save();
        $this->postJson("/api/orders/{$order->id}/decision", ['decision' => 'ok'])->assertOk();
    }

    public function test_delivery_validation_and_not_applicable(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);
        $plain = $this->makeOrder($user);

        $this->putJson("/api/orders/{$plain->id}/delivery", ['receiver_type' => 'self', 'method' => 'user'])
            ->assertStatus(422)->assertJsonPath('error_code', 'DELIVERY_NOT_APPLICABLE');

        $loop = $this->makeOrder($user, ['send_to_loop' => now()]);
        $this->putJson("/api/orders/{$loop->id}/delivery", ['receiver_type' => 'representative', 'method' => 'courier', 'receiver_mobile' => '0912'])
            ->assertStatus(422)->assertJsonValidationErrors(['receiver_name', 'receiver_mobile']);

        $this->putJson("/api/orders/{$loop->id}/delivery", ['receiver_type' => 'self', 'method' => 'loop_technician'])
            ->assertOk()
            ->assertJsonPath('data.receiver_type', 'self')
            ->assertJsonPath('data.receiver_mobile', $user->phone)
            ->assertJsonPath('data.status', 'pending');
    }
}
