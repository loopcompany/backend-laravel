<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderQuote;
use App\Models\OrderWorkflow;
use App\Services\AdminRoleService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\MakesPeople;
use Tests\Fakes\FakeSmsService;
use Tests\TestCase;

/**
 * سناریوهای کامل فرآیند سفارش از دید اپراتور پنل: هر قدم مثل کلیک روی دکمه در صفحه‌ی ویرایش سفارش
 * (باز کردن مودال، پر کردن فرم، ثبت) و پاسخ‌های اپ کاربر / تکنسین از API.
 */
class OrderWorkflowPanelE2ETest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(SmsService::class, new FakeSmsService());

        $service = app(AdminRoleService::class);
        $service->createBasicPermissions();
        $service->createBasicRoles();
        $this->admin = Admin::create(['name' => 'اپراتور', 'email' => 'operator@test.local', 'password' => 'password', 'staff_type' => Admin::STAFF_MANAGER]);
        $this->admin->forceFill(['is_active' => true])->save();
        $this->admin->assignRole(Role::where('name', 'super-admin')->where('guard_name', 'admin')->first());
    }

    // ───────────────────────────── ابزار ─────────────────────────────

    private function asAdmin(): void
    {
        $this->actingAs($this->admin, 'admin');
        $this->app['auth']->shouldUse('admin');
    }

    private function page(Order $order): Testable
    {
        $this->asAdmin();

        return Livewire::test(EditOrder::class, ['record' => $order->id]);
    }

    /** کلیک روی دکمه‌ی یک مرحله؛ اگر پنل خطا (نوتیف قرمز) نشان دهد تست با همان پیام شکست می‌خورد. */
    private function click(Order $order, string $action, array $data = [], bool $expectError = false, bool $invalid = false): Testable
    {
        session()->forget('filament.notifications');

        $page = $this->page($order)
            ->assertInfolistActionVisible(".{$action}Action", $action, 'workflowInfolist')
            ->mountInfolistAction(".{$action}Action", $action, 'workflowInfolist')
            ->setInfolistActionData($data)
            ->callMountedInfolistAction();

        $danger = collect(session('filament.notifications', []))->firstWhere('status', 'danger');

        // فرم نامعتبر: مودال باز می‌ماند و فراخوان خطاهای فیلد را بررسی می‌کند
        if ($invalid) {
            return $page;
        }

        if ($expectError) {
            $this->assertNotNull($danger, "انتظار خطا از «{$action}» بود ولی خطایی نشان داده نشد.");

            return $page;
        }

        $this->assertNull($danger, "«{$action}» خطا داد: " . ($danger['title'] ?? ''));
        $page->assertHasNoInfolistActionErrors();

        return $page;
    }

    private function assertButtons(Order $order, array $visible, array $hidden = []): void
    {
        $page = $this->page($order);
        foreach ($visible as $action) {
            $page->assertInfolistActionVisible(".{$action}Action", $action, 'workflowInfolist');
        }
        foreach ($hidden as $action) {
            $this->assertFalse($this->actionVisible($page, $action), "دکمه‌ی «{$action}» نباید نمایش داده شود.");
        }
    }

    private function actionVisible(Testable $page, string $action): bool
    {
        $container = $page->instance()->getInfolist('workflowInfolist')?->getComponent(".{$action}Action");

        return $container !== null && $container->getAction()?->isVisible();
    }

    private function stage(Order $order): string
    {
        return $order->fresh()->workflow_stage;
    }

    private function asUserApp(Order $order): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($order->user);
    }

    private function asTechnicianApp($technician): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($technician);
    }

    private function newOrder(array $userOverrides = [], array $orderOverrides = []): Order
    {
        $technicianCategory = $this->makeCategory();

        return $this->makeOrder($this->makeUser($userOverrides), array_merge([
            'category_id' => $technicianCategory,
            'date' => now()->addDays(2)->toDateString(),
            'time' => '10 - 12',
            'platform' => 'android',
            'pakar_price' => 400000,
        ], $orderOverrides));
    }

    // ───────────────────────────── سناریوها ─────────────────────────────

    /** مسیر کامل انجام در محل با همه‌ی گره‌ها: ناقص، پیش‌نویس، رد قیمت، رد تکنسین، رد پیشنهاد، رد قیمت جدید، تسویه. */
    public function test_onsite_path_with_all_detours(): void
    {
        $order = $this->newOrder(['account_type' => 'company']);
        $techA = $this->makeTechnician(['at_work' => '1']);
        $techB = $this->makeTechnician(['at_work' => '1']);

        // ۳ — بررسی
        $this->assertButtons($order, ['classify', 'mark_incomplete', 'approve_review', 'route_to_loop'], ['create_quote_initial', 'assign_technician', 'start_service']);
        $this->click($order, 'mark_incomplete', ['note' => 'مدل دستگاه و شماره سریال ثبت نشده']);
        $this->page($order)->assertSee('اطلاعات سفارش ناقص است');
        $this->click($order, 'approve_review', [], expectError: true); // دسته‌بندی ثبت نشده
        $this->click($order, 'classify', ['service_type' => 'onsite_repair', 'customer_type' => 'company'], invalid: true)
            ->assertHasInfolistActionErrors(['requester_name' => 'required']);
        $this->assertNull($order->fresh()->workflow->requester_name, 'بدون درخواست‌دهنده نباید ذخیره شود');
        $this->click($order, 'classify', ['service_type' => 'onsite_repair', 'customer_type' => 'company', 'requester_name' => 'خانم احمدی', 'requester_phone' => '02188888888', 'contract_reference' => 'QR-1405-12']);
        $this->click($order, 'approve_review', ['note' => 'تکمیل شد']);
        $this->assertSame(OrderWorkflow::STAGE_QUOTING, $this->stage($order));

        // ۴ — قیمت اولیه: پیش‌نویس، ارسال، رد کاربر، نسخه‌ی جدید، تأیید از اپ
        $this->assertButtons($order, ['create_quote_initial', 'route_to_loop_from_quoting'], ['send_quote', 'decide_quote', 'assign_technician']);
        $this->click($order, 'create_quote_initial', ['service_fee' => 500000, 'parts_fee' => 200000, 'estimated_duration' => '۲ ساعت', 'visit_date' => now()->addDays(3)->toDateString(), 'visit_time' => '12 - 14', 'send' => false]);
        $this->assertButtons($order, ['send_quote'], ['decide_quote', 'create_quote_initial']);
        $this->click($order, 'send_quote');
        $this->assertButtons($order, ['decide_quote'], ['send_quote', 'create_quote_initial']);

        $this->asUserApp($order);
        $this->postJson("/api/orders/{$order->id}/quote/decision", ['decision' => 'reject', 'note' => 'گران است'])->assertOk();
        $this->assertSame(OrderWorkflow::STAGE_QUOTING, $this->stage($order));

        $this->click($order, 'create_quote_initial', ['service_fee' => 450000, 'parts_fee' => 150000, 'estimated_duration' => '۲ ساعت', 'visit_date' => now()->addDays(3)->toDateString(), 'visit_time' => '12 - 14', 'send' => true]);
        $this->asUserApp($order);
        $this->getJson("/api/orders/{$order->id}/workflow")->assertOk()->assertJsonPath('data.awaiting_quote_decision', true)->assertJsonPath('data.current_quote.total', 600000);
        $this->postJson("/api/orders/{$order->id}/quote/decision", ['decision' => 'accept'])->assertOk();
        $this->assertSame(OrderWorkflow::STAGE_ASSIGNMENT, $this->stage($order));

        // ۵ — تخصیص: رد تکنسین الف، تخصیص به ب، تأیید از اپ
        $this->click($order, 'assign_technician', ['technician_id' => $techA->id, 'note' => 'ابزار لحیم همراه باشد']);
        $this->assertButtons($order, ['technician_response'], []);
        $this->click($order, 'technician_response', ['decision' => 'reject', 'reason' => 'در این بازه مأموریت دیگری دارم']);
        $this->assertNull($order->fresh()->technician_id);
        $this->click($order, 'assign_technician', ['technician_id' => $techB->id]);
        $this->asTechnicianApp($techB);
        $this->postJson("/api/technician/orders/{$order->id}/assignment", ['decision' => 'accept'])->assertOk();
        $this->assertSame(OrderWorkflow::STAGE_VISIT, $this->stage($order));

        // ۶ — مراجعه
        $this->assertButtons($order, ['record_set_off', 'visit_outcome'], ['record_arrival']);
        $this->click($order, 'record_set_off');
        $this->click($order, 'record_arrival');
        $this->click($order, 'visit_outcome', ['outcome' => 'needs_requote'], invalid: true)
            ->assertHasInfolistActionErrors(['note' => 'required']);
        $this->assertSame(OrderWorkflow::STAGE_VISIT, $this->stage($order), 'بدون شرح نباید ثبت شود');
        $this->click($order, 'visit_outcome', ['outcome' => 'needs_requote', 'note' => 'باتری باد کرده و باید تعویض شود']);
        $this->assertSame(OrderWorkflow::STAGE_REQUOTE, $this->stage($order));

        // ۷ — قیمت مجدد: پیشنهاد تکنسین ← رد پنل ← قیمت پنل ← رد کاربر ← ادامه با شرایط قبلی
        $this->asTechnicianApp($techB);
        $this->postJson("/api/technician/orders/{$order->id}/requote", ['service_fee' => 500000, 'parts_fee' => 3000000, 'estimated_duration' => '۱ ساعت', 'change_reason' => 'تعویض باتری'])->assertOk();
        $this->assertButtons($order, ['send_requote', 'discard_quote'], ['create_quote_revised', 'resolve_requote']);
        $this->click($order, 'discard_quote', ['reason' => 'قیمت باتری بالاتر از نرخ مصوب است']);
        $this->assertButtons($order, ['create_quote_revised', 'resolve_requote'], ['send_requote']);
        $this->click($order, 'create_quote_revised', ['service_fee' => 450000, 'parts_fee' => 2400000, 'estimated_duration' => '۱ ساعت', 'change_reason' => 'تعویض باتری اصلی', 'send' => true]);
        $this->click($order, 'decide_requote', ['decision' => 'reject', 'note' => 'باتری را خودم تهیه می‌کنم']);
        $this->click($order, 'resolve_requote', ['path' => 'continue', 'note' => 'ادامه با سرویس اولیه']);
        $this->assertSame(OrderWorkflow::STAGE_ONSITE_SERVICE, $this->stage($order));

        // ۸ — انجام در محل؛ در میانه نیاز به قطعه‌ی اضافه ← قیمت مجدد ← تأیید از اپ ← پایان
        $this->click($order, 'start_service');
        $this->click($order, 'request_requote', ['note' => 'نیاز به خمیر سیلیکون و پد حرارتی']);
        $this->click($order, 'create_quote_revised', ['service_fee' => 450000, 'parts_fee' => 350000, 'estimated_duration' => '۳۰ دقیقه', 'change_reason' => 'خمیر سیلیکون و پد', 'send' => true]);
        $this->asUserApp($order);
        $this->postJson("/api/orders/{$order->id}/quote/decision", ['decision' => 'accept'])->assertOk();
        $this->assertSame(OrderWorkflow::STAGE_ONSITE_SERVICE, $this->stage($order));
        $this->click($order, 'complete_service', ['service_actions' => 'تمیزکاری فن و تعویض خمیر', 'service_parts_used' => 'خمیر سیلیکون، پد حرارتی', 'service_result' => 'دمای پردازنده نرمال شد']);
        $this->assertSame(OrderWorkflow::STAGE_CLOSED, $this->stage($order));

        // ۱۱ — قیمت نهایی، تسویه، مختومه
        $this->click($order, 'close_file', [], expectError: true);
        $this->click($order, 'final_price', ['price' => 800000, 'note' => 'طبق قیمت تأییدشده']);
        $this->click($order, 'close_file', [], expectError: true); // تسویه نشده

        $this->asAdmin();
        Livewire::test(ListOrders::class)
            ->callTableAction('record_offline_payment', $order, ['payment_method' => 'card_to_card', 'payment_channel' => 'in_person', 'amount' => 800000])
            ->assertHasNoTableActionErrors();
        $this->click($order, 'close_file');

        $order->refresh();
        $this->assertSame(2, (int) $order->status);
        $this->assertSame(['initial', 'initial', 'revised', 'revised', 'revised', 'final'], $order->quotes()->pluck('kind')->all());
        $this->assertSame(
            ['rejected', 'accepted', 'discarded', 'rejected', 'accepted', 'recorded'],
            $order->quotes()->pluck('status')->all()
        );
        $this->page($order)->assertSee('پرونده مختومه شده است')->assertSee('تاریخچه');
    }

    /** مسیر ارجاع به شرکت: پیش‌پرداخت، رسید، تحویل نهایی و مختومه. */
    public function test_transfer_path_until_final_delivery(): void
    {
        $order = $this->newOrder();

        $this->click($order, 'classify', ['service_type' => 'combined', 'customer_type' => 'individual']);
        $this->click($order, 'approve_review');
        $this->click($order, 'route_to_loop_from_quoting', ['note' => 'نیاز به تعویض LCD در کارگاه']);
        $this->assertSame(OrderWorkflow::STAGE_TRANSFER, $this->stage($order));

        $this->assertButtons($order, ['start_transfer'], ['confirm_receipt']);
        $this->click($order, 'start_transfer', ['transfer_method' => 'courier', 'transfer_reason' => 'تعویض LCD', 'transfer_device_condition' => 'خط و خش روی بدنه'], invalid: true)
            ->assertHasInfolistActionErrors(['transfer_courier_info' => 'required']);
        $this->click($order, 'start_transfer', ['transfer_method' => 'courier', 'transfer_reason' => 'تعویض LCD', 'transfer_device_condition' => 'خط و خش روی بدنه', 'transfer_accessories' => 'شارژر', 'transfer_courier_info' => 'الوپیک ۹۹۸۱']);
        $this->assertNotNull($order->fresh()->send_to_loop);

        $this->click($order, 'confirm_receipt', ['transfer_received_by' => 'انباردار', 'transfer_received_at' => now()->toDateTimeString()]);
        $this->assertSame(OrderWorkflow::STAGE_CLOSED, $this->stage($order));
        $this->assertNotNull($order->fresh()->workflow->transfer_receipt_number);

        // تعمیرات داخل شرکت: برآورد و پیش‌پرداخت از فرم ویرایش
        $this->click($order, 'final_price', ['price' => 3000000], expectError: true); // پیش‌پرداخت نشده
        $this->page($order)
            ->fillForm(['loop_cost_estimate' => 3000000, 'prepayment' => 50, 'duration' => 3])
            ->call('save')->assertHasNoFormErrors();
        $this->page($order)->fillForm(['prepayment_payment_status' => 1])->call('save')->assertHasNoFormErrors();

        $this->click($order, 'final_price', ['price' => 3200000]);
        $order->refresh()->forceFill(['payment_status' => 1])->save();
        $this->click($order, 'close_file', [], expectError: true); // هنوز تحویل نهایی نشده

        $this->page($order)->fillForm(['delivery_status' => 'delivered', 'delivery_receiver_type' => 'self', 'delivery_method' => 'courier'])->call('save')->assertHasNoFormErrors();
        $this->click($order, 'close_file');
        $this->assertSame(2, (int) $order->fresh()->status);
        $this->assertSame(OrderWorkflow::STAGE_CLOSED, $this->stage($order));
    }

    /** لغو با دستگاه در اختیار شرکت و عودت؛ نتیجه‌ی «کاربر موافق نیست». */
    public function test_cancel_paths(): void
    {
        $order = $this->newOrder();
        $this->click($order, 'route_to_loop', ['note' => 'دریافت دستگاه برای بررسی']);
        $this->click($order, 'start_transfer', ['transfer_method' => 'user', 'transfer_reason' => 'بررسی', 'transfer_device_condition' => 'سالم']);

        $this->page($order)
            ->assertActionVisible('workflow_cancel')
            ->callAction('workflow_cancel', ['cancel_reason_type' => 'user_request', 'cancel_reason' => 'کاربر منصرف شد', 'cancel_financial_note' => 'هزینه‌ی کارشناسی دریافت نمی‌شود'])
            ->assertHasNoActionErrors();

        $order->refresh();
        $this->assertSame(OrderWorkflow::STAGE_CANCELLED, $order->workflow_stage);
        $this->assertSame(5, (int) $order->status);
        $this->assertTrue($order->workflow->device_return_required, 'دستگاه در شرکت است');
        $this->page($order)->assertSee('در انتظار عودت دستگاه')->assertActionHidden('workflow_cancel');
        $this->click($order, 'confirm_device_returned', ['note' => 'تحویل حضوری به خود کاربر']);
        $this->assertNotNull($order->fresh()->returned_at);

        // کاربر در محل با ادامه موافق نیست ← لغو با علت پیش‌فرض
        $order2 = $this->newOrder();
        $tech = $this->makeTechnician(['at_work' => '1']);
        $this->click($order2, 'classify', ['service_type' => 'onsite_diagnosis', 'customer_type' => 'individual']);
        $this->click($order2, 'approve_review');
        $this->click($order2, 'create_quote_initial', ['service_fee' => 300000, 'estimated_duration' => '۱ ساعت', 'visit_date' => now()->addDay()->toDateString(), 'visit_time' => '10 - 12', 'requires_user_approval' => false, 'send' => true]);
        $this->assertSame(OrderWorkflow::STAGE_ASSIGNMENT, $this->stage($order2), 'بدون نیاز به تأیید کاربر مستقیم به تخصیص');
        $this->click($order2, 'assign_technician', ['technician_id' => $tech->id]);
        $this->click($order2, 'technician_response', ['decision' => 'accept']);
        $this->click($order2, 'visit_outcome', ['outcome' => 'user_declined', 'note' => 'کاربر هزینه‌ی کارشناسی را نپذیرفت']);
        $this->page($order2)->assertSee('کاربر با ادامه‌ی خدمت موافقت نکرده است');
        $this->assertButtons($order2, [], ['visit_outcome']);
        $this->page($order2)->mountAction('workflow_cancel')->assertActionDataSet(['cancel_reason_type' => 'user_request']);
    }

    /** انصراف تکنسین پس از حرکت (اپ) و مأموریت ناموفق از پنل؛ هماهنگی مجدد. */
    public function test_recoordination_paths(): void
    {
        $order = $this->newOrder();
        $tech = $this->makeTechnician(['at_work' => '1']);
        $this->click($order, 'classify', ['service_type' => 'onsite_repair', 'customer_type' => 'individual']);
        $this->click($order, 'approve_review');
        $this->click($order, 'create_quote_initial', ['service_fee' => 300000, 'estimated_duration' => '۱ ساعت', 'visit_date' => now()->addDay()->toDateString(), 'visit_time' => '10 - 12', 'send' => true]);
        $this->click($order, 'decide_quote', ['decision' => 'accept', 'note' => 'تأیید تلفنی']);
        $this->click($order, 'assign_technician', ['technician_id' => $tech->id]);

        $this->asTechnicianApp($tech);
        $this->postJson("/api/technician/orders/{$order->id}/set-off")->assertOk();
        $this->postJson("/api/technician/orders/{$order->id}/cancel", ['technician_cancel_reason' => 'تصادف در مسیر'])->assertOk();
        $this->assertSame(OrderWorkflow::STAGE_ASSIGNMENT, $this->stage($order));
        $this->page($order)->assertSee('مأموریت ناموفق');

        $this->click($order, 'assign_technician', ['technician_id' => $tech->id, 'date' => now()->addDays(2)->toDateString(), 'time' => '14 - 16']);
        $this->click($order, 'technician_response', ['decision' => 'accept']);
        $this->click($order, 'record_set_off');
        $this->click($order, 'record_arrival');
        $this->click($order, 'visit_outcome', ['outcome' => 'mission_failed', 'note' => 'کاربر در محل حضور نداشت']);
        $order->refresh();
        $this->assertSame(OrderWorkflow::STAGE_ASSIGNMENT, $order->workflow_stage);
        $this->assertSame('14 - 16', $order->time);
        $this->assertNull($order->set_off_at);
    }

    /** فرم ویرایش روی صفحه‌ی مراحل ذخیره می‌شود و مرحله را خراب نمی‌کند؛ لیست وضعیت درست را نشان می‌دهد. */
    public function test_edit_form_and_list_stay_consistent(): void
    {
        $order = $this->newOrder();
        $this->click($order, 'classify', ['service_type' => 'onsite_repair', 'customer_type' => 'individual']);
        $this->click($order, 'approve_review');

        $this->page($order)->fillForm(['technician_des' => 'کاربر ساعت ۱۲ به بعد در دسترس است', 'time' => '16 - 20'])->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(OrderResource::getUrl('edit', ['record' => $order]));
        $this->assertSame(OrderWorkflow::STAGE_QUOTING, $this->stage($order));

        // اختصاص تکنسین از لیست فقط در مرحله‌ی تخصیص نمایش داده می‌شود
        $this->asAdmin();
        Livewire::test(ListOrders::class)->assertTableActionHidden('assign_technician', $order->fresh());

        $ready = $this->newOrder();
        $tech = $this->makeTechnician(['at_work' => '1', 'technician_type' => 'تکنسین میدانی']);
        $this->click($ready, 'classify', ['service_type' => 'onsite_repair', 'customer_type' => 'individual']);
        $this->click($ready, 'approve_review');
        $this->click($ready, 'create_quote_initial', ['service_fee' => 300000, 'estimated_duration' => '۱ ساعت', 'visit_date' => now()->addDay()->toDateString(), 'visit_time' => '10 - 12', 'requires_user_approval' => false, 'send' => true]);
        $this->asAdmin();
        $ready->refresh();
        Livewire::test(ListOrders::class)
            ->assertTableActionVisible('assign_technician', $ready)
            ->mountTableAction('assign_technician', $ready)
            ->setTableActionData(['technician_id' => $tech->id, 'note' => 'از لیست'])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();
        $this->assertSame($tech->id, $ready->fresh()->technician_id);
        $this->assertSame('pending', $ready->fresh()->workflow->assignment_status);

        $this->asAdmin();
        Livewire::test(ListOrders::class)
            ->assertCanSeeTableRecords([$order])
            ->assertSee('در انتظار ثبت قیمت')
            ->set('activeTab', OrderWorkflow::STAGE_QUOTING)
            ->assertCanSeeTableRecords([$order])
            ->set('activeTab', OrderWorkflow::STAGE_REVIEW)
            ->assertCanNotSeeTableRecords([$order]);
    }
}
