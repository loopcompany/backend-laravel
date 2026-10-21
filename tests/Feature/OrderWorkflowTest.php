<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderQuote;
use App\Models\OrderWorkflow;
use App\Services\AdminRoleService;
use App\Services\OrderWorkflowException;
use App\Services\OrderWorkflowService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Concerns\MakesPeople;
use Tests\Fakes\FakeSmsService;
use Tests\TestCase;

/**
 * سند «فرآیند ثبت، بررسی، قیمت‌گذاری و ارجاع سفارش خدمات تعمیر و ارتقای دستگاه» (پنل سفارشات).
 */
class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    private OrderWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(SmsService::class, new FakeSmsService());
        $this->workflow = app(OrderWorkflowService::class);
    }

    private function actingAsAdmin(): Admin
    {
        $service = app(AdminRoleService::class);
        $service->createBasicPermissions();
        $service->createBasicRoles();

        $admin = Admin::create(['name' => 'Root', 'email' => 'root@test.local', 'password' => 'password', 'staff_type' => Admin::STAFF_MANAGER]);
        $admin->forceFill(['is_active' => true])->save();
        $admin->assignRole(Role::where('name', 'super-admin')->where('guard_name', 'admin')->first());
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    /** سفارش آماده‌ی تخصیص: بررسی تأیید و قیمت اولیه توسط کاربر پذیرفته شده. */
    private function orderReadyForAssignment(array $overrides = []): Order
    {
        $order = $this->makeOrder($this->makeUser(), $overrides);
        $this->workflow->classify($order, ['service_type' => 'onsite_repair', 'customer_type' => 'individual']);
        $this->workflow->approveReview($order);
        $quote = $this->workflow->createQuote($order, OrderQuote::KIND_INITIAL, ['service_fee' => 500000, 'visit_date' => now()->addDay()->toDateString(), 'visit_time' => '10 - 12']);
        $this->workflow->decideQuote($quote, true, 'تلفنی');

        return $order->fresh();
    }

    public function test_new_order_enters_review_with_registration_log(): void
    {
        $order = $this->makeOrder($this->makeUser(), ['platform' => 'android', 'date' => '2026-11-01', 'time' => '10 - 12']);

        $this->assertSame(OrderWorkflow::STAGE_REVIEW, $order->workflow_stage);
        $log = $order->stageLogs()->first();
        $this->assertSame('created', $log->action);
        $this->assertSame('2026-11-01', $log->meta['requested_date']);
        $this->assertStringContainsString('اپلیکیشن', $log->note);
    }

    public function test_review_requires_classification_and_requester_for_organizations(): void
    {
        $this->actingAsAdmin();
        $order = $this->makeOrder($this->makeUser(['account_type' => 'company']));

        $this->assertThrows(fn () => $this->workflow->approveReview($order), OrderWorkflowException::class);

        $this->workflow->markIncomplete($order, 'مدل دستگاه ثبت نشده');
        $this->assertSame('incomplete', $order->fresh()->workflow->review_status);

        $this->workflow->classify($order, ['service_type' => 'onsite_upgrade', 'customer_type' => 'company']);
        $this->assertThrows(fn () => $this->workflow->approveReview($order), OrderWorkflowException::class, 'درخواست‌دهنده');

        $this->workflow->classify($order, ['requester_name' => 'آقای مدیری', 'contract_reference' => 'C-12']);
        $this->workflow->approveReview($order);

        $this->assertSame(OrderWorkflow::STAGE_QUOTING, $order->fresh()->workflow_stage);
        $this->assertSame('approved', $order->fresh()->workflow->review_status);
    }

    public function test_assignment_is_blocked_until_review_and_quote_are_done(): void
    {
        $order = $this->makeOrder($this->makeUser());
        $technician = $this->makeTechnician();

        $this->assertThrows(fn () => $this->workflow->assignTechnician($order, $technician->id), OrderWorkflowException::class, 'مجاز نیست');
    }

    public function test_initial_quote_rejection_keeps_order_on_hold_and_new_version_supersedes(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user);
        $this->workflow->classify($order, ['service_type' => 'onsite_diagnosis', 'customer_type' => 'individual']);
        $this->workflow->approveReview($order);

        $v1 = $this->workflow->createQuote($order, OrderQuote::KIND_INITIAL, ['service_fee' => 300000, 'visit_date' => '2026-11-02', 'visit_time' => '12 - 14']);
        $this->assertSame(OrderQuote::STATUS_SENT, $v1->status);
        $this->assertTrue($v1->is_estimate);
        $this->assertSame('2026-11-02', (string) $order->fresh()->date);

        Sanctum::actingAs($user);
        $this->postJson("/api/orders/{$order->id}/quote/decision", ['decision' => 'reject'])->assertStatus(422);
        $this->postJson("/api/orders/{$order->id}/quote/decision", ['decision' => 'reject', 'note' => 'گران است'])
            ->assertOk()
            ->assertJsonPath('data.current_quote.status', OrderQuote::STATUS_REJECTED);

        $this->assertSame(OrderWorkflow::STAGE_QUOTING, $order->fresh()->workflow_stage);

        $v2 = $this->workflow->createQuote($order->fresh(), OrderQuote::KIND_INITIAL, ['service_fee' => 250000, 'visit_date' => '2026-11-02', 'visit_time' => '12 - 14'], send: false);
        $this->assertSame(2, $v2->version);
        $this->assertSame(OrderQuote::STATUS_PENDING_REVIEW, $v2->status);

        // پیش‌نویس برای کاربر نمایش داده نمی‌شود
        $this->postJson('/api/orders/detail', ['orderId' => $order->id])->assertOk()
            ->assertJsonPath('workflow.current_quote.version', 1);

        $this->workflow->sendQuote($v2);
        $this->postJson("/api/orders/{$order->id}/quote/decision", ['decision' => 'accept'])->assertOk();

        $order->refresh();
        $this->assertSame(OrderWorkflow::STAGE_ASSIGNMENT, $order->workflow_stage);
        $this->assertNotNull($order->user_initial_accept);
        $this->assertSame(OrderQuote::STATUS_ACCEPTED, $v2->fresh()->status);
    }

    public function test_full_onsite_flow_with_requote_and_file_closing(): void
    {
        $order = $this->orderReadyForAssignment();
        $technician = $this->makeTechnician();

        $this->workflow->assignTechnician($order, $technician->id, 'هویه همراه داشته باشد');
        $order->refresh();
        $this->assertSame($technician->id, $order->technician_id);
        $this->assertSame(1, (int) $order->status);
        $this->assertSame('pending', $order->workflow->assignment_status);

        // ۵-۴ — تأیید مأموریت از اپ تکنسین
        Sanctum::actingAs($technician);
        $this->getJson("/api/technician/orders/{$order->id}/detail")->assertOk()
            ->assertJsonPath('workflow.assignment_status', 'pending')
            ->assertJsonPath('workflow.assignment_note', 'هویه همراه داشته باشد');
        $this->postJson("/api/technician/orders/{$order->id}/assignment", ['decision' => 'accept'])->assertOk();
        $this->assertSame(OrderWorkflow::STAGE_VISIT, $order->fresh()->workflow_stage);

        // ۶ — نتیجه: نیاز به قیمت جدید؛ ۷-۱ پیشنهاد تکنسین
        $this->postJson("/api/technician/orders/{$order->id}/visit-outcome", ['outcome' => 'needs_requote'])->assertStatus(422);
        $this->postJson("/api/technician/orders/{$order->id}/visit-outcome", ['outcome' => 'needs_requote', 'note' => 'رم خراب است'])->assertOk();
        $this->postJson("/api/technician/orders/{$order->id}/requote", [
            'service_fee' => 600000, 'parts_fee' => 1500000, 'estimated_duration' => '۲ ساعت', 'change_reason' => 'تعویض رم',
        ])->assertOk()->assertJsonPath('data.status', OrderQuote::STATUS_PENDING_REVIEW);

        $order->refresh();
        $this->assertSame(OrderWorkflow::STAGE_REQUOTE, $order->workflow_stage);
        $revised = $this->workflow->openQuote($order);
        $this->assertSame('technician', $revised->proposed_by_type);
        $this->assertSame(2100000, $revised->total);

        // ۸-۱ — بدون تأیید کاربر خدمت شروع نمی‌شود
        $this->actingAsAdmin();
        $this->workflow->sendQuote($revised);
        $this->workflow->decideQuote($revised->fresh(), true);
        $order->refresh();
        $this->assertSame(OrderWorkflow::STAGE_ONSITE_SERVICE, $order->workflow_stage);

        $this->workflow->startService($order);
        $this->workflow->completeOnsiteService($order->fresh(), ['service_actions' => 'تعویض رم', 'service_parts_used' => 'رم ۸ گیگ', 'service_result' => 'دستگاه سالم است']);

        $order->refresh();
        $this->assertSame(OrderWorkflow::STAGE_CLOSED, $order->workflow_stage);
        $this->assertSame('done_onsite', $order->workflow->field_result);
        $this->assertNotNull($order->done_in_place);
        $this->assertNotSame(2, (int) $order->status, 'پایان مأموریت میدانی پرونده را نمی‌بندد');

        // ۱۱-۵ — بدون قیمت نهایی و تسویه مختومه نمی‌شود
        $this->assertThrows(fn () => $this->workflow->closeFile($order), OrderWorkflowException::class);
        $this->workflow->recordFinalPrice($order, 2100000, 'طبق قیمت تأییدشده');
        $order->refresh()->forceFill(['payment_status' => 1])->save();
        $this->workflow->closeFile($order->fresh());

        $order->refresh();
        $this->assertSame(2, (int) $order->status);
        $this->assertSame(2100000, (int) $order->technician_price);
        $this->assertSame(
            [OrderQuote::KIND_INITIAL, OrderQuote::KIND_REVISED, OrderQuote::KIND_FINAL],
            $order->quotes()->pluck('kind')->all()
        );
        $this->assertTrue($order->stageLogs()->where('action', 'file_closed')->exists());
    }

    public function test_technician_rejection_returns_order_for_reassignment(): void
    {
        $order = $this->orderReadyForAssignment();
        $technician = $this->makeTechnician();
        $this->workflow->assignTechnician($order, $technician->id);

        Sanctum::actingAs($technician);
        $this->postJson("/api/technician/orders/{$order->id}/assignment", ['decision' => 'reject'])->assertStatus(422);
        $this->postJson("/api/technician/orders/{$order->id}/assignment", ['decision' => 'reject', 'reason' => 'خارج از محدوده'])->assertOk();

        $order->refresh();
        $this->assertNull($order->technician_id);
        $this->assertSame(0, (int) $order->status);
        $this->assertSame(OrderWorkflow::STAGE_ASSIGNMENT, $order->workflow_stage);
        $this->assertSame('rejected', $order->workflow->assignment_status);
        $this->assertSame('technician', $order->stageLogs()->where('action', 'technician_rejected')->first()->actor_type);
    }

    public function test_failed_mission_goes_back_to_assignment_for_recoordination(): void
    {
        $order = $this->orderReadyForAssignment();
        $technician = $this->makeTechnician();
        $this->workflow->assignTechnician($order, $technician->id);
        $this->workflow->technicianResponse($order->fresh(), true);
        $this->workflow->recordSetOff($order->fresh());
        $this->workflow->recordArrival($order->fresh());

        $this->workflow->recordVisitOutcome($order->fresh(), 'mission_failed', 'کاربر در محل نبود');

        $order->refresh();
        $this->assertSame(OrderWorkflow::STAGE_ASSIGNMENT, $order->workflow_stage);
        $this->assertNull($order->set_off_at);
        $this->assertSame('failed_needs_recoordination', $order->workflow->field_result);

        $this->workflow->assignTechnician($order, $technician->id);
        $this->assertNull($order->fresh()->workflow->field_result);
    }

    public function test_transfer_to_loop_flow_and_receipt(): void
    {
        $this->actingAsAdmin();
        $order = $this->makeOrder($this->makeUser());

        $this->workflow->routeToLoop($order, 'نیاز به تعویض برد');
        $this->assertSame(OrderWorkflow::STAGE_TRANSFER, $order->fresh()->workflow_stage);

        $this->assertThrows(fn () => $this->workflow->startTransfer($order->fresh(), ['transfer_method' => 'courier', 'transfer_reason' => 'x', 'transfer_device_condition' => 'خط و خش']), OrderWorkflowException::class, 'پیک');

        $this->workflow->startTransfer($order->fresh(), [
            'transfer_method' => 'courier', 'transfer_reason' => 'نیاز به تعویض برد',
            'transfer_device_condition' => 'لپ‌تاپ ایسوس، خط و خش روی درب', 'transfer_accessories' => 'شارژر',
            'transfer_courier_info' => 'الوپیک — بسته‌ی ۱ عددی',
        ]);
        $order->refresh();
        $this->assertNotNull($order->send_to_loop);
        $this->assertNotNull($order->prepayment, 'درصد پیش‌پرداخت پیش‌فرض لوپ');

        $receipt = $this->workflow->confirmTransferReceipt($order, ['transfer_received_by' => 'انباردار']);
        $order->refresh();
        $this->assertSame(OrderWorkflow::STAGE_CLOSED, $order->workflow_stage);
        $this->assertSame('transferred_to_loop', $order->workflow->field_result);
        $this->assertSame($receipt, $order->workflow->transfer_receipt_number);
        $this->assertContains('دستگاه هنوز به کاربر تحویل نهایی نشده', $this->workflow->closeBlockers($order));
    }

    public function test_cancel_with_device_in_custody_requires_return(): void
    {
        $this->actingAsAdmin();
        $order = $this->makeOrder($this->makeUser());
        $this->workflow->routeToLoop($order, 'دریافت دستگاه');
        $this->workflow->startTransfer($order->fresh(), ['transfer_method' => 'technician', 'transfer_reason' => 'دریافت', 'transfer_device_condition' => 'سالم']);

        $this->workflow->cancel($order->fresh(), ['cancel_reason_type' => 'parts_unavailable', 'cancel_reason' => 'قطعه موجود نیست', 'cancel_financial_note' => 'بدون هزینه']);

        $order->refresh();
        $this->assertSame(5, (int) $order->status);
        $this->assertSame('admin', $order->cancelled_by);
        $this->assertSame(OrderWorkflow::STAGE_CANCELLED, $order->workflow_stage);
        $this->assertTrue($order->workflow->device_return_required);
        $this->assertSame('cancelled_not_feasible', $order->workflow->field_result);

        $this->workflow->confirmDeviceReturned($order, 'تحویل به خود کاربر');
        $this->assertNotNull($order->fresh()->returned_at);
        $this->assertThrows(fn () => $this->workflow->cancel($order->fresh(), ['cancel_reason_type' => 'other', 'cancel_reason' => 'x']), OrderWorkflowException::class);
    }

    public function test_app_actions_keep_stage_in_sync_and_are_logged(): void
    {
        // لغو از اپ کاربر
        $user = $this->makeUser();
        $order = $this->makeOrder($user);
        Sanctum::actingAs($user);
        $this->postJson('/api/orders/cancel', ['orderId' => $order->id])->assertOk();
        $order->refresh();
        $this->assertSame(OrderWorkflow::STAGE_CANCELLED, $order->workflow_stage);
        $this->assertSame('user_request', $order->workflow->cancel_reason_type);
        $log = $order->stageLogs()->where('action', 'status_changed')->first();
        $this->assertSame('user', $log->actor_type);

        // انجام در محل از اپ تکنسین
        $order = $this->orderReadyForAssignment();
        $technician = $this->makeTechnician();
        $this->workflow->assignTechnician($order, $technician->id);
        Sanctum::actingAs($technician);
        $this->postJson("/api/technician/orders/{$order->id}/set-off")->assertOk();
        $this->assertSame(OrderWorkflow::STAGE_VISIT, $order->fresh()->workflow_stage, 'حرکت = تأیید ضمنی مأموریت');
        $this->postJson("/api/technician/orders/{$order->id}/done-in-place", ['technician_in_place_description' => 'انجام شد'])->assertOk();

        $order->refresh();
        $this->assertSame(OrderWorkflow::STAGE_CLOSED, $order->workflow_stage);
        $this->assertSame('done_onsite', $order->workflow->field_result);
    }

    public function test_technician_withdraw_after_set_off_is_failed_mission_needing_recoordination(): void
    {
        $order = $this->orderReadyForAssignment();
        $order2 = $this->orderReadyForAssignment();
        $technician = $this->makeTechnician();
        $this->workflow->assignTechnician($order, $technician->id);
        $this->workflow->assignTechnician($order2, $technician->id);

        Sanctum::actingAs($technician);
        $this->postJson("/api/technician/orders/{$order->id}/set-off")->assertOk();
        $this->postJson("/api/technician/orders/{$order->id}/cancel", ['technician_cancel_reason' => 'کاربر پاسخ نمی‌دهد'])->assertOk();

        $order->refresh();
        $this->assertSame(OrderWorkflow::STAGE_ASSIGNMENT, $order->workflow_stage);
        $this->assertSame(0, (int) $order->status);
        $this->assertNull($order->technician_id);
        $this->assertNull($order->set_off_at);
        $this->assertSame('mission_failed', $order->workflow->visit_outcome);
        $this->assertSame('failed_needs_recoordination', $order->workflow->field_result);

        // پس از شروع خدمت انصراف ممکن نیست
        $order2->refresh()->forceFill(['started_at' => now()])->saveQuietly();
        $this->postJson("/api/technician/orders/{$order2->id}/cancel", ['technician_cancel_reason' => 'x'])->assertStatus(409);
        $this->assertSame($technician->id, $order2->fresh()->technician_id);
    }

    public function test_technician_pricing_authority_sends_requote_directly_within_limit(): void
    {
        $technician = $this->makeTechnician();
        $order = $this->orderReadyForAssignment();
        $this->workflow->assignTechnician($order, $technician->id);
        $this->workflow->technicianResponse($order->fresh(), true);
        $this->workflow->recordVisitOutcome($order->fresh(), 'needs_requote', 'نیاز به قطعه');
        Sanctum::actingAs($technician);

        // بدون تنظیم، همه‌ی پیشنهادها به بررسی پنل می‌روند
        $this->postJson("/api/technician/orders/{$order->id}/requote", ['service_fee' => 520000, 'estimated_duration' => '۱ ساعت', 'change_reason' => 'کابل'])
            ->assertOk()->assertJsonPath('data.status', OrderQuote::STATUS_PENDING_REVIEW);

        \App\Models\AppSetting::put(\App\Models\AppSetting::TECHNICIAN_REQUOTE_LIMIT_PERCENT, 10);

        // قیمت تأییدشده ۵۰۰٬۰۰۰ است؛ تا ۵۵۰٬۰۰۰ مستقیم برای کاربر می‌رود
        $this->postJson("/api/technician/orders/{$order->id}/requote", ['service_fee' => 540000, 'estimated_duration' => '۱ ساعت', 'change_reason' => 'کابل'])
            ->assertOk()->assertJsonPath('data.status', OrderQuote::STATUS_SENT);
        $this->getJson("/api/technician/orders/{$order->id}/detail")->assertOk()->assertJsonPath('workflow.requote_auto_send_max_total', 550000);

        $this->postJson("/api/technician/orders/{$order->id}/requote", ['service_fee' => 400000, 'parts_fee' => 200000, 'estimated_duration' => '۲ ساعت', 'change_reason' => 'رم'])
            ->assertOk()->assertJsonPath('data.status', OrderQuote::STATUS_PENDING_REVIEW);

        // تأیید کاربر همچنان لازم است
        $this->assertSame(OrderWorkflow::STAGE_REQUOTE, $order->fresh()->workflow_stage);
    }

    public function test_edit_form_locks_workflow_fields_and_phone_order_starts_in_review(): void
    {
        $this->actingAsAdmin();
        $order = $this->makeOrder($this->makeUser(), ['time' => '10 - 12', 'date' => now()->toDateString()]);

        Livewire::test(OrderResource\Pages\EditOrder::class, ['record' => $order->id])
            ->assertFormFieldIsDisabled('status')
            ->assertFormFieldIsDisabled('technician_id')
            ->assertFormFieldIsDisabled('technician_price')
            ->fillForm(['status' => 2, 'technician_price' => 999])
            ->call('save')
            ->assertHasNoFormErrors();

        $order->refresh();
        $this->assertSame(0, (int) $order->status);
        $this->assertNull($order->technician_price);
        $this->assertSame(OrderWorkflow::STAGE_REVIEW, $order->workflow_stage);
    }

    public function test_edit_page_hosts_stepper_and_runs_stage_actions(): void
    {
        $this->actingAsAdmin();
        $order = $this->makeOrder($this->makeUser(), ['time' => '10 - 12', 'date' => now()->toDateString()]);

        Livewire::test(OrderResource\Pages\EditOrder::class, ['record' => $order->id])
            ->assertOk()
            ->assertSee('مراحل سفارش #' . $order->id)
            ->assertSee('ویرایش اطلاعات سفارش')
            ->callInfolistAction('.classifyAction', 'classify', ['service_type' => 'onsite_repair', 'customer_type' => 'individual'], infolistName: 'workflowInfolist')
            ->assertHasNoInfolistActionErrors()
            ->assertRedirect(OrderResource::getUrl('edit', ['record' => $order]));

        // پس از هر اقدام صفحه با مرحله‌ی جدید دوباره باز می‌شود
        Livewire::test(OrderResource\Pages\EditOrder::class, ['record' => $order->id])
            ->callInfolistAction('.approve_reviewAction', 'approve_review', [], infolistName: 'workflowInfolist')
            ->assertRedirect(OrderResource::getUrl('edit', ['record' => $order]));

        $this->assertSame(OrderWorkflow::STAGE_QUOTING, $order->fresh()->workflow_stage);

        // لیست: وضعیت هر ردیف با رنگ مرحله، بدون دکمه‌ی نمایش، کلیک ردیف → ویرایش
        Livewire::test(OrderResource\Pages\ListOrders::class)
            ->assertOk()
            ->assertSee('ow-row-quoting', false)
            ->assertSee('در انتظار ثبت قیمت')
            ->assertTableActionDoesNotExist('view');
    }

    /** باگ: تصاویری که اپ هنگام ثبت سفارش در file_paths می‌فرستد در صفحه‌ی ویرایش نمایش داده نمی‌شد. */
    public function test_user_images_sent_with_order_are_shown_on_edit_page(): void
    {
        $this->actingAsAdmin();
        $user = $this->makeUser();
        $order = $this->makeOrder($user, ['time' => '10 - 12', 'date' => now()->toDateString()]);
        Sanctum::actingAs($user);

        // همان مسیری که اپ می‌رود: uploadMultiple → file_paths در ثبت سفارش → order_galleries
        \App\Models\OrderGallery::create(['order_id' => $order->id, 'image_path' => 'profile/first-photo.jpg']);
        \App\Models\OrderGallery::create(['order_id' => $order->id, 'image_path' => 'profile/second-photo.png']);

        Livewire::test(OrderResource\Pages\EditOrder::class, ['record' => $order->id])
            ->assertOk()
            ->assertSee('تصاویر و فایل‌های ارسالی کاربر (2)')
            ->assertSee('/storage/profile/first-photo.jpg', false)
            ->assertSee('/storage/profile/second-photo.png', false);

        $empty = $this->makeOrder($user, ['time' => '10 - 12', 'date' => now()->toDateString()]);
        Livewire::test(OrderResource\Pages\EditOrder::class, ['record' => $empty->id])
            ->assertSee('کاربر هنگام ثبت سفارش تصویر یا فایلی ارسال نکرده است.');
    }

    public function test_panel_status_edit_is_synced_with_stage(): void
    {
        $this->actingAsAdmin();
        $order = $this->makeOrder($this->makeUser());

        $order->update(['status' => 5]);

        $order->refresh();
        $this->assertSame(OrderWorkflow::STAGE_CANCELLED, $order->workflow_stage);
        $this->assertSame('admin', $order->stageLogs()->where('action', 'status_changed')->first()->actor_type);
    }

    public function test_order_page_renders_every_stage_and_cancel_action_works(): void
    {
        $this->actingAsAdmin();
        $order = $this->orderReadyForAssignment(['platform' => 'web']);

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->assertOk()
            ->assertSee('قیمت‌گذاری اولیه و زمان‌بندی')
            ->assertSee('تخصیص تکنسین')
            ->assertSee('وب‌سایت')
            ->assertSee('تاریخچه');

        $legacy = $this->makeOrder($this->makeUser(), ['technician_id' => $this->makeTechnician()->id, 'status' => 1]);
        Livewire::test(ViewOrder::class, ['record' => $legacy->id])->assertOk();

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction('workflow_cancel', ['cancel_reason_type' => 'user_request', 'cancel_reason' => 'کاربر منصرف شد'])
            ->assertHasNoActionErrors();

        $this->assertSame(OrderWorkflow::STAGE_CANCELLED, $order->fresh()->workflow_stage);

        Livewire::test(ViewOrder::class, ['record' => $order->id])->assertOk()->assertSee('سفارش لغو شده است');
        $this->get(OrderResource::getUrl('index'))->assertOk();
    }
}
