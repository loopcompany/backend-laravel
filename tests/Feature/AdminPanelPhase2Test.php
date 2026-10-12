<?php

namespace Tests\Feature;

use App\Filament\Pages\FinancialSettings;
use App\Filament\Resources;
use App\Filament\Resources\OrderProcurementResource\Pages\EditOrderProcurement;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrganizationEditRequestResource\Pages\ListOrganizationEditRequests;
use App\Filament\Resources\ReferralWithdrawalResource\Pages\ListReferralWithdrawals;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Admin;
use App\Models\AppSetting;
use App\Models\Order;
use App\Models\OrderProcurement;
use App\Models\Organization;
use App\Models\OrganizationEditRequest;
use App\Models\ReferralCode;
use App\Models\ReferralWithdrawal;
use App\Services\AdminRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

/**
 * صفحه‌های جدید پنل (بندهای ۴ و ۵ «توضیحات پنل» + صف‌های سند اپ کاربر).
 */
class AdminPanelPhase2Test extends TestCase
{
    use RefreshDatabase, MakesPeople;

    protected function setUp(): void
    {
        parent::setUp();

        $service = app(AdminRoleService::class);
        $service->createBasicPermissions();
        $service->createBasicRoles();

        $admin = Admin::create(['name' => 'Root', 'email' => 'root@test.local', 'password' => 'password', 'staff_type' => Admin::STAFF_MANAGER]);
        $admin->forceFill(['is_active' => true])->save();
        $admin->assignRole(Role::where('name', 'super-admin')->where('guard_name', 'admin')->first());
        $this->actingAs($admin, 'admin');
    }

    private function procurementOrder(): Order
    {
        $user = $this->makeUser(['account_type' => 'company']);
        Organization::factory()->create(['user_id' => $user->id]);
        $order = $this->makeOrder($user);
        $procurement = $order->procurement()->create(['status' => 'awaiting_quote', 'prepayment_percent' => 60]);
        $procurement->items()->create(['title' => 'لپ تاپ', 'condition' => 'new', 'quantity' => 2]);

        return $order;
    }

    public function test_new_pages_render(): void
    {
        $order = $this->procurementOrder();
        $loopOrder = $this->makeOrder($this->makeUser(), ['send_to_loop' => now(), 'time' => '9 - 10']);

        $urls = [
            Resources\OrderProcurementResource::getUrl('index'),
            Resources\OrderProcurementResource::getUrl('edit', ['record' => $order->procurement]),
            Resources\OrganizationEditRequestResource::getUrl('index'),
            Resources\ReferralWithdrawalResource::getUrl('index'),
            Resources\ReferralCommissionResource::getUrl('index'),
            Resources\OrderResource::getUrl('edit', ['record' => $loopOrder]),
            Resources\OrderResource::getUrl('view', ['record' => $loopOrder]),
            Resources\OrderResource::getUrl('create'),
            Resources\AdminResource::getUrl('create'),
            FinancialSettings::getUrl(),
            filament()->getProfileUrl(),
        ];

        foreach ($urls as $url) {
            $this->assertSame(200, $this->get($url)->status(), $url);
        }

        // بند ۴: «تاریخ / ساعت اعزام محصول / تکنسین» به‌جای «تاریخ / ساعت سرویس»
        $this->get(Resources\OrderResource::getUrl('edit', ['record' => $loopOrder]))
            ->assertSee('تاریخ اعزام محصول / تکنسین')
            ->assertSee('ساعت اعزام محصول / تکنسین')
            ->assertDontSee('تاریخ سرویس')
            ->assertSee('مشخصات تحویل');
    }

    public function test_dispatch_slots_and_auto_filled_times_are_read_only(): void
    {
        $order = $this->makeOrder($this->makeUser(), ['time' => '9 - 10', 'date' => now()->toDateString(), 'set_off_at' => now()->subHour()]);
        $setOff = $order->set_off_at->toDateTimeString();

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->assertFormFieldIsDisabled('set_off_at')
            ->assertFormFieldIsDisabled('arrived_at')
            ->assertFormFieldIsDisabled('send_to_loop')
            ->fillForm(['time' => '14 - 16', 'set_off_at' => now()->addDay()])
            ->call('save')
            ->assertHasNoFormErrors();

        $order->refresh();
        $this->assertSame('14 - 16', $order->time);
        $this->assertSame($setOff, $order->set_off_at->toDateTimeString());
    }

    public function test_loop_order_cannot_be_done_before_prepayment(): void
    {
        $order = $this->makeOrder($this->makeUser(), [
            'status' => 1, 'send_to_loop' => now(), 'prepayment' => 60, 'loop_cost_estimate' => 1000000, 'time' => '10 - 12', 'date' => now()->toDateString(),
        ]);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->assertFormFieldIsDisabled('technician_price')
            ->fillForm(['status' => 2])
            ->call('save')
            ->assertNotified('پیش‌پرداخت پرداخت نشده است');

        $this->assertSame(1, (int) $order->fresh()->status);
    }

    public function test_procurement_quote_from_panel(): void
    {
        $order = $this->procurementOrder();
        $procurement = $order->procurement;
        $itemKey = 'record-' . $procurement->items->first()->id;

        Livewire::test(EditOrderProcurement::class, ['record' => $procurement->getRouteKey()])
            ->fillForm([
                'prepayment_percent' => 50,
                "items.{$itemKey}.unit_price" => 30000000,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->callAction('quote');

        $procurement->refresh();
        $this->assertSame(OrderProcurement::STATUS_QUOTED, $procurement->status);
        $this->assertSame(60000000, $procurement->total_amount);
        $this->assertSame(30000000, $procurement->prepayment_amount);
        $this->assertNotNull($procurement->valid_until);
    }

    public function test_settings_edit_requests_agents_and_withdrawals(): void
    {
        Livewire::test(FinancialSettings::class)
            ->fillForm([
                AppSetting::BUSINESS_END_TIME => '18:30',
                AppSetting::PROCUREMENT_PREPAYMENT_PERCENT => 70,
                AppSetting::LOOP_PREPAYMENT_PERCENT => 50,
                AppSetting::REFERRAL_MIN_WITHDRAW_AMOUNT => 100000,
            ])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('18:30', AppSetting::get(AppSetting::BUSINESS_END_TIME));
        $this->assertSame(70, AppSetting::int(AppSetting::PROCUREMENT_PREPAYMENT_PERCENT));

        // درخواست ویرایش: تأیید با انتخاب فیلدها
        $orgUser = $this->makeUser(['account_type' => 'company']);
        $organization = Organization::factory()->create(['user_id' => $orgUser->id]);
        // پیش‌فرض فرم تأیید = فیلدهایی که کاربر درخواست کرده
        $request = $organization->editRequests()->create([
            'user_id' => $orgUser->id, 'status' => 'pending', 'description' => 'x', 'letter_path' => 'a.pdf', 'fields' => ['organization_name'],
        ]);

        Livewire::test(ListOrganizationEditRequests::class)
            ->callTableAction('approve', $request)
            ->assertHasNoTableActionErrors();
        $this->assertSame(['organization_name'], $request->fresh()->unlocked_fields);
        $this->assertSame(OrganizationEditRequest::STATUS_APPROVED, $request->fresh()->status);

        // مأمور خرید از فهرست کاربران
        Livewire::test(ListUsers::class)
            ->callTableAction('referral_agent', $orgUser, ['is_referral_agent' => true, 'commission_percent' => 12, 'discount_percent' => 3])
            ->assertHasNoTableActionErrors();
        $this->assertTrue($orgUser->fresh()->is_referral_agent);
        $this->assertSame(12, ReferralCode::where('user_id', $orgUser->id)->where('multi_use', true)->value('commission_percent'));

        // صف برداشت: ثبت واریز
        $withdrawal = ReferralWithdrawal::create(['user_id' => $orgUser->id, 'amount' => 100000, 'method' => 'sheba', 'status' => 'pending', 'sheba_number' => 'IR062960000000100324200001']);
        Livewire::test(ListReferralWithdrawals::class)
            ->callTableAction('pay', $withdrawal, ['tracking_code' => 'TRK-1'])
            ->assertHasNoTableActionErrors();
        $this->assertSame('paid', $withdrawal->fresh()->status);
        $this->assertSame('TRK-1', $withdrawal->fresh()->tracking_code);
    }
}
