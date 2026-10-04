<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Models\Admin;
use App\Models\AppVersion;
use App\Models\EditRequest;
use App\Models\Organization;
use App\Models\OrganizationDocument;
use App\Models\ReferralCode;
use App\Models\User;
use App\Models\UserTransaction;
use App\Services\EditRequestApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

/**
 * بخش‌های ۲، ۳ و ۶ نیازمندی‌های اپ کاربر/سازمانی: کد معرف، حساب سازمانی، مدارک و کاربران مجاز،
 * نسخه‌ی اپ و روش/کانال پرداخت.
 */
class OrganizationAccountAndPaymentTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    private function organizationUser(array $org = []): User
    {
        $provinceId = DB::table('provinces')->insertGetId(['title' => 'تهران', 'created_at' => now(), 'updated_at' => now()]);
        $cityId = DB::table('cities')->insertGetId(['province_id' => $provinceId, 'title' => 'تهران', 'created_at' => now(), 'updated_at' => now()]);
        $regionId = DB::table('regions')->insertGetId(['city_id' => $cityId, 'title' => 'منطقه ۵', 'code' => 5, 'created_at' => now(), 'updated_at' => now()]);

        $user = $this->makeUser(['account_type' => 'company', 'melicode' => '10101234567', 'region_id' => $regionId, 'region' => '5']);
        Organization::factory()->create(array_merge([
            'user_id' => $user->id,
            'organization_name' => 'شرکت آزمون',
            'organization_phone' => '02188888888',
            'profile_status' => 'approved',
            'contract_status' => 'approved',
        ], $org));

        return $user->fresh();
    }

    // ----------------------------------------------------------------- پروفایل سازمان

    public function test_profile_returns_identity_fields_and_verification_status(): void
    {
        $user = $this->organizationUser(['registration_number' => '123456', 'economic_code' => '411111111111']);
        Sanctum::actingAs($user);

        $this->getJson('/api/organization/profile')
            ->assertOk()
            ->assertJsonPath('data.melicode', '10101234567')
            ->assertJsonPath('data.registration_number', '123456')
            ->assertJsonPath('data.economic_code', '411111111111')
            ->assertJsonPath('data.verification_status', 'approved')
            ->assertJsonPath('data.verification_reason', null);

        $user->organization->update(['suspended_at' => now(), 'suspension_reason' => 'مدارک ناقص است']);

        $this->getJson('/api/organization/profile')
            ->assertJsonPath('data.verification_status', 'suspended')
            ->assertJsonPath('data.verification_reason', 'مدارک ناقص است');
        $this->getJson('/api/organization/profile/status')->assertJsonPath('data.verification_status', 'suspended');
        $this->assertFalse($user->organization->fresh()->hasCompleteAccess());
    }

    public function test_rejected_profile_reports_rejection_reason(): void
    {
        $user = $this->organizationUser(['profile_status' => 'rejected', 'profile_rejection_reason' => 'شناسه ملی نادرست']);
        Sanctum::actingAs($user);

        $this->getJson('/api/organization/profile')
            ->assertJsonPath('data.verification_status', 'rejected')
            ->assertJsonPath('data.verification_reason', 'شناسه ملی نادرست');
    }

    public function test_update_profile_puts_identity_fields_into_edit_request_and_approval_applies_them(): void
    {
        $user = $this->organizationUser(['registration_number' => '111', 'economic_code' => '999']);
        Sanctum::actingAs($user);

        $this->postJson('/api/organization/update-profile', ['melicode' => '1234']) // نامعتبر
            ->assertStatus(422);

        $this->postJson('/api/organization/update-profile', [
            'melicode' => '14001234567',
            'registration_number' => '654321',
            'economic_code' => '',
        ])->assertOk()
            ->assertJsonPath('data.edited_organization.melicode', '14001234567')
            ->assertJsonPath('data.edited_organization.registration_number', '654321')
            ->assertJsonPath('data.edited_organization.economic_code', null)
            // تا تأیید ادمین، اطلاعات فعلی تغییر نمی‌کند
            ->assertJsonPath('data.melicode', '10101234567');

        app(EditRequestApprovalService::class)->approve(EditRequest::sole());

        $this->assertSame('14001234567', $user->fresh()->melicode);
        $this->assertSame('654321', $user->organization->fresh()->registration_number);
        $this->assertNull($user->organization->fresh()->economic_code);
    }

    // ----------------------------------------------------------------- مدارک و کاربران مجاز

    public function test_documents_upload_list_signed_url_and_delete(): void
    {
        Storage::fake('local');
        $user = $this->organizationUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/organization/documents', ['file' => UploadedFile::fake()->create('x.exe', 10)])->assertStatus(422);

        $doc = $this->postJson('/api/organization/documents', [
            'file' => UploadedFile::fake()->create('اساسنامه.pdf', 200, 'application/pdf'),
            'title' => 'اساسنامه',
        ])->assertCreated()
            ->assertJsonPath('data.title', 'اساسنامه')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.status_label', 'در انتظار بررسی')
            ->json('data');

        $this->getJson('/api/organization/documents')->assertOk()->assertJsonCount(1, 'data');

        // لینک امضاشده بدون توکن باز می‌شود؛ بدون امضا نه
        $this->app['auth']->forgetGuards();
        $this->get(parse_url($doc['url'], PHP_URL_PATH) . '?' . parse_url($doc['url'], PHP_URL_QUERY))->assertOk();
        $this->get("/api/organization/documents/{$doc['id']}/file")->assertForbidden();

        // سازمان دیگر نمی‌تواند حذف کند
        Sanctum::actingAs($this->organizationUser());
        $this->deleteJson("/api/organization/documents/{$doc['id']}")->assertNotFound();

        Sanctum::actingAs($user);
        $this->deleteJson("/api/organization/documents/{$doc['id']}")->assertOk();
        $this->assertSame(0, OrganizationDocument::count());
        Storage::disk('local')->assertDirectoryEmpty("organization-documents/{$user->organization->id}");
    }

    public function test_authorized_users_crud(): void
    {
        $user = $this->organizationUser();
        Sanctum::actingAs($user);

        $id = $this->postJson('/api/organization/users', ['full_name' => 'مریم احمدی', 'mobile' => '09121234567', 'role' => 'مأمور خرید'])
            ->assertCreated()->assertJsonPath('data.role', 'مأمور خرید')->json('data.id');
        $this->postJson('/api/organization/users', ['full_name' => 'تکراری', 'mobile' => '09121234567'])->assertStatus(422);
        $this->postJson('/api/organization/users', ['full_name' => 'بد', 'mobile' => '0912'])->assertStatus(422);

        $this->getJson('/api/organization/users')->assertOk()
            ->assertJsonPath('data.0.full_name', 'مریم احمدی')
            ->assertJsonPath('data.0.mobile', '09121234567');

        $this->deleteJson("/api/organization/users/{$id}")->assertOk();
        $this->getJson('/api/organization/users')->assertJsonCount(0, 'data');

        Sanctum::actingAs($this->makeUser());
        $this->getJson('/api/organization/users')->assertForbidden();
    }

    // ----------------------------------------------------------------- نسخه‌ی اپ

    public function test_app_version_endpoint(): void
    {
        $this->getJson('/api/app/version?platform=android&current_version=2.74.3')
            ->assertOk()->assertJsonPath('data.update_available', false)->assertJsonPath('data.force', false);

        AppVersion::create([
            'app' => 'user', 'platform' => 'android', 'latest_version' => '2.75.0',
            'min_supported_version' => '2.70.0', 'update_url' => 'https://example.com/app.apk',
        ]);

        $this->getJson('/api/app/version?platform=android&current_version=2.74.3')
            ->assertJsonPath('data.latest_version', '2.75.0')
            ->assertJsonPath('data.update_available', true)
            ->assertJsonPath('data.force', false)
            ->assertJsonPath('data.update_url', 'https://example.com/app.apk');

        $this->getJson('/api/app/version?platform=android&current_version=2.69.9')->assertJsonPath('data.force', true);
        $this->getJson('/api/app/version?platform=android&current_version=2.75.0')->assertJsonPath('data.update_available', false);
        $this->getJson('/api/app/version?platform=ios&current_version=1.0.0')->assertJsonPath('data.update_available', false);
        $this->getJson('/api/app/version?platform=symbian')->assertStatus(422);
    }

    // ----------------------------------------------------------------- کد معرف

    public function test_each_referral_code_has_its_own_discount_and_commission(): void
    {
        $buyer = $this->makeUser();
        $owner = $this->makeUser();
        ReferralCode::create(['user_id' => $owner->id, 'code' => 'LOOP-AAAAAA', 'discount_percent' => 7, 'commission_percent' => 3, 'status' => 'active']);
        ReferralCode::create(['user_id' => $owner->id, 'code' => 'LOOP-BBBBBB', 'discount_percent' => 15, 'commission_percent' => 0, 'status' => 'active']);
        Sanctum::actingAs($buyer);

        $this->postJson('/api/referral-codes/check', ['code' => 'loop-aaaaaa'])
            ->assertJsonPath('data.discount_percent', 7)
            ->assertJsonMissingPath('data.commission_percent');
        $this->postJson('/api/referral-codes/check', ['code' => 'LOOP-BBBBBB'])->assertJsonPath('data.discount_percent', 15);

        $order = $this->makeOrder($buyer, ['technician_price' => 1000000, 'referral_discount_percent' => 7, 'referral_commission_percent' => 3]);
        // تخفیف خریدار ۷٪ ← ۹۳۰٬۰۰۰ ؛ پورسانت ۳٪ از آن ← ۲۷٬۹۰۰
        $this->assertEquals(70000, $order->referralDiscountAmount());
        $this->assertEquals(27900, $order->referralCommissionAmount());
    }

    // ----------------------------------------------------------------- روش و کانال پرداخت

    public function test_orders_and_transactions_expose_payment_method_channel_and_remaining(): void
    {
        $user = $this->makeUser();
        $unpaid = $this->makeOrder($user, ['technician_price' => 500000]);
        $paidLegacy = $this->makeOrder($user, ['technician_price' => 500000, 'payment_status' => 1, 'platform' => 'android']);
        $paidOnSite = $this->makeOrder($user, ['technician_price' => 500000, 'payment_status' => 1, 'platform' => 'web']);
        $cash = $this->makeOrder($user, ['technician_price' => 500000, 'payment_method' => 'remaining', 'payment_channel' => 'in_person', 'remaining_amount' => 200000]);
        UserTransaction::create(['user_id' => $user->id, 'price' => 100000, 'type' => 1, 'status' => 100]);
        UserTransaction::create(['user_id' => $user->id, 'price' => 300000, 'type' => 4, 'status' => 100, 'payment_method' => 'cash', 'payment_channel' => 'in_person', 'order_id' => $cash->id]);
        Sanctum::actingAs($user);

        $orders = collect($this->getJson('/api/orders')->assertOk()->json('data'))->keyBy('id');
        $this->assertSame(['unpaid', null, null], [$orders[$unpaid->id]['payment_method'], $orders[$unpaid->id]['payment_channel'], $orders[$unpaid->id]['remaining_amount']]);
        $this->assertSame(['in_app', 'app'], [$orders[$paidLegacy->id]['payment_method'], $orders[$paidLegacy->id]['payment_channel']]);
        $this->assertSame('site', $orders[$paidOnSite->id]['payment_channel']);
        $this->assertSame(['remaining', 'in_person', 200000], [$orders[$cash->id]['payment_method'], $orders[$cash->id]['payment_channel'], $orders[$cash->id]['remaining_amount']]);

        $this->getJson("/api/orders/{$cash->id}")->assertOk()->assertJsonPath('payment_method', 'remaining')->assertJsonPath('remaining_amount', 200000);
        $this->getJson("/api/orders/{$unpaid->id}")->assertOk()->assertJsonPath('id', $unpaid->id);

        $transactions = collect($this->getJson('/api/wallet/transactions')->assertOk()->json('data.transactions'));
        $this->assertEqualsCanonicalizing(['in_app', 'cash'], $transactions->pluck('payment_method')->all());
        $this->assertEqualsCanonicalizing(['app', 'in_person'], $transactions->pluck('payment_channel')->all());
    }

    public function test_admin_records_offline_payment_from_order_table(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
        foreach (['view-orders', 'edit-orders'] as $p) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $p, 'guard_name' => 'admin']));
        }
        $admin = Admin::create(['name' => 'Root', 'email' => 'root@test.local', 'password' => 'password', 'staff_type' => Admin::STAFF_MANAGER]);
        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        $user = $this->makeUser();
        $order = $this->makeOrder($user, ['technician_price' => 800000, 'status' => 2]);

        Livewire::test(ListOrders::class)
            ->callTableAction('record_offline_payment', $order, [
                'payment_method' => 'card_to_card',
                'payment_channel' => 'in_person',
                'amount' => 800000,
                'reference' => '123456',
            ])
            ->assertHasNoTableActionErrors();

        $order->refresh();
        $this->assertSame(1, (int) $order->payment_status);
        $this->assertSame('card_to_card', $order->payment_method);
        $this->assertSame('in_person', $order->payment_channel);
        $this->assertDatabaseHas('user_transactions', [
            'order_id' => $order->id, 'type' => 4, 'payment_method' => 'card_to_card', 'payment_channel' => 'in_person',
        ]);
    }
}
