<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Models\Admin;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Services\AdminRoleService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\MakesPeople;
use Tests\Fakes\FakeSmsService;
use Tests\TestCase;

/**
 * سند «نیازمندی‌های بک‌اند — تغییرات اپ کاربر (۱۴۰۵/۰۷/۲۶)»:
 * ۱-۱ لغو از پنل همیشه ۵ ، ۱-۲ cancelled_by / cancelled_at ، ۱-۳ exclude_cancelled / only_cancelled
 * و ۲ تأیید مشخصات تحویل از پنل (delivery.confirmed_at).
 */
class OrderCancelSourceAndDeliveryConfirmTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(SmsService::class, new FakeSmsService());
    }

    private function actingAsAdmin(): void
    {
        $service = app(AdminRoleService::class);
        $service->createBasicPermissions();
        $service->createBasicRoles();

        $admin = Admin::create(['name' => 'Root', 'email' => 'root@test.local', 'password' => 'password', 'staff_type' => Admin::STAFF_MANAGER]);
        $admin->forceFill(['is_active' => true])->save();
        $admin->assignRole(Role::where('name', 'super-admin')->where('guard_name', 'admin')->first());
        $this->actingAs($admin, 'admin');
    }

    public function test_user_cancel_is_reported_as_cancelled_by_user(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/orders/cancel', ['orderId' => $order->id])->assertOk();

        $order->refresh();
        $this->assertSame(3, (int) $order->status);
        $this->assertSame('user', $order->cancelled_by);
        $this->assertNotNull($order->cancelled_at);

        $this->postJson('/api/orders/detail', ['orderId' => $order->id])->assertOk()
            ->assertJsonPath('cancelled_by', 'user');
        $list = $this->getJson('/api/orders')->assertOk();
        $this->assertSame('user', collect($list->json('data'))->firstWhere('id', $order->id)['cancelled_by']);
    }

    public function test_technician_cancel_is_reported_as_cancelled_by_technician(): void
    {
        $technician = $this->makeTechnician();
        $order = $this->makeOrder($this->makeUser(), ['technician_id' => $technician->id, 'status' => 1]);
        Sanctum::actingAs($technician);

        $this->postJson("/api/technician/orders/{$order->id}/cancel", ['technician_cancel_reason' => 'خرابی خودرو'])
            ->assertOk();

        $order->refresh();
        $this->assertSame(4, (int) $order->status);
        $this->assertSame('technician', $order->cancelled_by);

        $item = collect($this->getJson('/api/technician/orders')->assertOk()->json('data.orders') ?? $this->getJson('/api/technician/orders')->json('data'))
            ->firstWhere('id', $order->id);
        $this->assertSame('technician', $item['cancelled_by'] ?? null);
        $this->assertNotNull($item['cancelled_at'] ?? null);
    }

    public function test_reject_decision_path_sets_user_source(): void
    {
        $order = $this->makeOrder($this->makeUser(), ['status' => 1]);

        app(OrderRepository::class)->rejectOrder($order->id, 'گران است');

        $order->refresh();
        $this->assertSame('user', $order->cancelled_by);
        $this->assertNotNull($order->cancelled_at);
    }

    public function test_panel_cancel_is_admin_and_reverting_clears_the_source(): void
    {
        $order = $this->makeOrder($this->makeUser(), ['status' => 1]);

        $order->update(['status' => 5]);
        $this->assertSame('admin', $order->fresh()->cancelled_by);

        $order->update(['status' => 1]);
        $order->refresh();
        $this->assertNull($order->cancelled_by);
        $this->assertNull($order->cancelled_at);
    }

    public function test_panel_cannot_save_user_or_technician_cancel_codes(): void
    {
        $this->actingAsAdmin();
        $order = $this->makeOrder($this->makeUser(), ['status' => 0, 'time' => '10 - 12', 'date' => now()->toDateString()]);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(['status' => 3])
            ->call('save')
            ->assertNotified('این وضعیت از پنل قابل ثبت نیست');
        $this->assertSame(0, (int) $order->fresh()->status);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(['status' => 5])
            ->call('save')
            ->assertHasNoFormErrors();
        $order->refresh();
        $this->assertSame(5, (int) $order->status);
        $this->assertSame('admin', $order->cancelled_by);
    }

    public function test_orders_list_cancel_filters(): void
    {
        $user = $this->makeUser();
        $active = $this->makeOrder($user, ['status' => 1]);
        $cancelled = $this->makeOrder($user, ['status' => 5]);
        Sanctum::actingAs($user);

        $ids = fn ($query) => collect($this->getJson('/api/orders?' . $query)->assertOk()->json('data'))->pluck('id')->all();

        $this->assertSame([$active->id], $ids('exclude_cancelled=1'));
        $this->assertSame([$cancelled->id], $ids('only_cancelled=1'));
        $this->assertEqualsCanonicalizing([$active->id, $cancelled->id], $ids('per_page=10'));
    }

    public function test_delivery_needs_admin_confirmation_and_user_edit_resets_it(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user, ['send_to_loop' => now(), 'time' => '10 - 12', 'date' => now()->toDateString()]);
        Sanctum::actingAs($user);

        $this->putJson("/api/orders/{$order->id}/delivery", [
            'receiver_type' => 'representative', 'method' => 'courier',
            'receiver_name' => 'علی رضایی', 'receiver_mobile' => '09121112233',
        ])->assertOk()->assertJsonPath('data.confirmed_at', null);

        // تأیید و ارسال برای کاربر از صفحه‌ی نمایش سفارش در پنل
        $this->actingAsAdmin();
        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('confirm_delivery')
            ->assertNotified('مشخصات تحویل تأیید و برای کاربر ارسال شد');
        $this->assertNotNull($order->fresh()->delivery_confirmed_at);

        Sanctum::actingAs($user);
        $this->assertNotNull($this->postJson('/api/orders/detail', ['orderId' => $order->id])->json('delivery.confirmed_at'));

        // ارسال دوباره‌ی همان مشخصات، تأیید را باطل نمی‌کند
        $this->putJson("/api/orders/{$order->id}/delivery", [
            'receiver_type' => 'representative', 'method' => 'courier',
            'receiver_name' => 'علی رضایی', 'receiver_mobile' => '09121112233',
        ])->assertOk();
        $this->assertNotNull($order->fresh()->delivery_confirmed_at);

        // تغییر روش ارسال ← ادمین باید دوباره تأیید کند
        $this->putJson("/api/orders/{$order->id}/delivery", [
            'receiver_type' => 'representative', 'method' => 'loop_technician',
            'receiver_name' => 'علی رضایی', 'receiver_mobile' => '09121112233',
        ])->assertOk()->assertJsonPath('data.confirmed_at', null);
        $this->assertNull($order->fresh()->delivery_confirmed_at);
    }

    public function test_delivery_can_be_confirmed_from_the_edit_form(): void
    {
        $this->actingAsAdmin();
        $order = $this->makeOrder($this->makeUser(), ['send_to_loop' => now(), 'time' => '10 - 12', 'date' => now()->toDateString()]);

        // کلید بخش، کلید کامپوننت لایه است (بدون پیشوند data.) و همان‌طور که مرورگر صدا می‌زند mount می‌شود
        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(['delivery_receiver_type' => 'self', 'delivery_method' => 'user'])
            ->call('mountFormComponentAction', 'delivery_section', 'confirm_delivery')
            ->call('callMountedFormComponentAction')
            ->assertNotified('مشخصات تحویل تأیید و برای کاربر ارسال شد');

        $order->refresh();
        $this->assertSame('self', $order->delivery_receiver_type);
        $this->assertSame('user', $order->delivery_method);
        $this->assertNotNull($order->delivery_confirmed_at);
    }
}
