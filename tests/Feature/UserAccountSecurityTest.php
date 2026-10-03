<?php

namespace Tests\Feature;

use App\Models\AccountActivity;
use App\Models\AccountDeletionRequest;
use App\Models\Organization;
use App\Models\SecurityAlert;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use App\Services\Security\AccountDeletionService;
use App\Services\SmsService;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\Concerns\MakesPeople;
use Tests\Fakes\FakeSmsService;
use Tests\TestCase;

/**
 * امنیت حساب کاربر عادی و سازمانی/شرکتی (بخش ۷ BACKEND_REQUIREMENTS_USER_ORG_APP).
 */
class UserAccountSecurityTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    private FakeSmsService $sms;

    /** @var \Mockery\MockInterface */
    private $push;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sms = new FakeSmsService();
        $this->app->instance(SmsService::class, $this->sms);

        $this->push = Mockery::mock(FirebaseNotificationService::class);
        $this->push->shouldReceive('sendToUser')->andReturn(['sent' => 1, 'failed' => 0, 'removed' => 0])->byDefault();
        $this->app->instance(FirebaseNotificationService::class, $this->push);
    }

    // ----------------------------------------------------------------- helpers

    private function login(User $user, string $password = 'secret123'): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->postJson('/api/auth/login', ['phone' => $user->phone, 'password' => $password]);
    }

    private function token(User $user): string
    {
        return $this->login($user)->assertOk()->json('data.token');
    }

    private function as(string $token, string $method, string $uri, array $data = []): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($token)->json($method, $uri, $data);
    }

    private function device(string $id, string $model = 'Galaxy A25'): array
    {
        return [
            'device_id' => $id, 'platform' => 'android', 'device_type' => 'phone', 'device_model' => $model,
            'os_name' => 'Android', 'os_version' => '14', 'app_version' => '2.74.3',
        ];
    }

    // ----------------------------------------------------------------- summary & devices

    public function test_summary_has_the_documented_shape(): void
    {
        $user = $this->makeUser();
        $token = $this->token($user);

        $this->as($token, 'GET', '/api/account/security')
            ->assertOk()
            ->assertJsonPath('data.two_factor.enabled', false)
            ->assertJsonPath('data.mobile.number', $user->phone)
            ->assertJsonPath('data.mobile.verified', true)
            ->assertJsonPath('data.active_devices_count', 1)
            ->assertJsonPath('data.unread_alerts_count', 0)
            ->assertJsonPath('data.sessions_supported', true);
    }

    public function test_device_register_list_logout_and_logout_others(): void
    {
        $user = $this->makeUser();
        $a = $this->token($user);
        $this->as($a, 'POST', '/api/account/devices/register', $this->device('dev-a'))->assertOk();
        $b = $this->token($user);
        $this->as($b, 'POST', '/api/account/devices/register', $this->device('dev-b', 'iPhone 15') + ['platform' => 'ios'])->assertOk();
        $c = $this->token($user);
        $this->as($c, 'POST', '/api/account/devices/register', $this->device('dev-c'))->assertOk();

        $devices = collect($this->as($a, 'GET', '/api/account/devices')->assertOk()->json('data'))->keyBy('id');
        $this->assertCount(3, $devices);
        $this->assertTrue($devices['dev-a']['is_current']);
        $this->assertFalse($devices['dev-b']['is_current']);
        $this->assertSame('iPhone 15', $devices['dev-b']['device_model']);
        $this->assertMatchesRegularExpression('/\+03:30$/', $devices['dev-a']['last_activity_at']);

        // خروج از یک دستگاه دیگر
        $this->as($a, 'DELETE', '/api/account/devices/dev-b')->assertOk();
        $this->as($b, 'GET', '/api/account/devices')->assertUnauthorized();
        $this->as($a, 'DELETE', '/api/account/devices/dev-a')->assertStatus(422)->assertJsonPath('error_code', 'CURRENT_DEVICE');

        // خروج از سایر دستگاه‌ها: فقط توکن همین درخواست می‌ماند
        $this->as($a, 'POST', '/api/account/devices/logout-others', ['current_device_id' => 'dev-a'])
            ->assertOk()->assertJsonPath('data.revoked_devices', 1);
        $this->as($c, 'GET', '/api/account/devices')->assertUnauthorized();
        $this->assertSame(['dev-a'], $user->tokens()->pluck('device_id')->all());

        $types = AccountActivity::where('account_id', $user->id)->pluck('type')->all();
        $this->assertContains('device_logout', $types);
        $this->assertContains('logout_other_devices', $types);
    }

    public function test_relogin_on_same_device_replaces_old_session(): void
    {
        $user = $this->makeUser();
        $this->as($this->token($user), 'POST', '/api/account/devices/register', $this->device('dev-a'));
        $this->as($this->token($user), 'POST', '/api/account/devices/register', $this->device('dev-a'));

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_new_device_login_creates_alert_only_for_a_second_unknown_device(): void
    {
        $user = $this->makeUser();
        $this->as($this->token($user), 'POST', '/api/account/devices/register', $this->device('dev-a'));
        $this->as($this->token($user), 'POST', '/api/account/devices/register', $this->device('dev-a'));
        $this->assertSame(0, SecurityAlert::where('type', 'new_device_login')->count());

        $this->as($this->token($user), 'POST', '/api/account/devices/register', $this->device('dev-b'));
        $this->assertSame(1, SecurityAlert::where('type', 'new_device_login')->count());
    }

    public function test_sessions_list_and_end_session(): void
    {
        $user = $this->makeUser();
        $a = $this->token($user);
        $this->token($user);
        $otherId = $user->tokens()->orderByDesc('id')->value('id');

        $sessions = collect($this->as($a, 'GET', '/api/account/sessions')->assertOk()->json('data'));
        $this->assertCount(2, $sessions);
        $this->assertSame('active', $sessions->first()['status']);
        $this->assertSame(1, $sessions->where('is_current', true)->count());

        $this->as($a, 'DELETE', "/api/account/sessions/{$otherId}")->assertOk();
        $this->assertSame(1, $user->tokens()->count());
        $this->as($a, 'DELETE', '/api/account/sessions/999999')->assertNotFound();
    }

    public function test_technician_token_cannot_use_account_routes(): void
    {
        $tech = $this->makeTechnician();
        $token = $tech->createToken('technician-app')->plainTextToken;

        $this->as($token, 'GET', '/api/account/security')->assertForbidden()->assertJsonPath('error_code', 'USER_ACCOUNT_ONLY');
    }

    // ----------------------------------------------------------------- activities & alerts

    public function test_login_events_are_recorded_and_repeated_failures_raise_one_alert(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 6; $i++) {
            $this->login($user, 'wrong-password')->assertUnauthorized();
        }
        $token = $this->token($user);

        $activities = $this->as($token, 'GET', '/api/account/activities?page=1')->assertOk();
        $this->assertSame('login_success', $activities->json('data.0.type'));
        $this->assertSame('success', $activities->json('data.0.result'));
        $this->assertSame('failed', $activities->json('data.1.result'));
        $this->assertSame(7, $activities->json('meta.total'));

        $this->assertSame(1, SecurityAlert::where('type', 'multiple_failed_logins')->count());
    }

    public function test_alerts_read_read_all_and_settings(): void
    {
        $user = $this->makeUser();
        $token = $this->token($user);
        $user->forceFill(['password' => bcrypt('another-pass')])->save(); // password_changed (high)
        $user->forceFill(['password' => bcrypt('third-pass')])->save();

        $alerts = $this->as($token, 'GET', '/api/account/security-alerts')->assertOk()->json('data');
        $this->assertCount(2, $alerts);
        $this->assertSame('password_changed', $alerts[0]['type']);
        $this->assertFalse($alerts[0]['is_read']);
        foreach (['title', 'message', 'severity', 'suggested_action', 'created_at', 'device', 'ip'] as $key) {
            $this->assertArrayHasKey($key, $alerts[0]);
        }

        $this->as($token, 'POST', "/api/account/security-alerts/{$alerts[0]['id']}/read")->assertOk();
        $this->as($token, 'GET', '/api/account/security')->assertJsonPath('data.unread_alerts_count', 1);
        $this->as($token, 'POST', '/api/account/security-alerts/read-all')->assertOk();
        $this->as($token, 'GET', '/api/account/security')->assertJsonPath('data.unread_alerts_count', 0);

        $this->as($token, 'GET', '/api/account/security-alerts/settings')->assertJsonPath('data.notifications_enabled', true);
        $this->as($token, 'PUT', '/api/account/security-alerts/settings', ['notifications_enabled' => false])
            ->assertOk()->assertJsonPath('data.notifications_enabled', false);

        // رمز عبور قبلی کاربر دیگر کار نمی‌کند؛ فقط ورود با رمز جدید
        $this->assertContains('password_changed', AccountActivity::where('account_id', $user->id)->pluck('type')->all());
    }

    public function test_critical_alerts_are_pushed_even_when_notifications_are_off(): void
    {
        $user = $this->makeUser();
        $token = $this->token($user);
        $this->as($token, 'PUT', '/api/account/security-alerts/settings', ['notifications_enabled' => false]);

        $this->push->shouldReceive('sendToUser')
            ->once()
            ->withArgs(fn ($account, $title, $body, $data) => $data['type'] === 'security_alert' && $data['severity'] === 'critical')
            ->andReturn(['sent' => 1, 'failed' => 0, 'removed' => 0]);

        // high: ذخیره می‌شود ولی push نمی‌شود
        $user->forceFill(['password' => bcrypt('another-pass')])->save();

        // critical: تغییر موبایل
        $this->as($token, 'POST', '/api/account/mobile/send-code', ['mobile' => '09129998877'])->assertOk();
        $this->as($token, 'POST', '/api/account/mobile/verify', [
            'mobile' => '09129998877', 'code' => $this->sms->lastCodeFor('09129998877'),
        ])->assertOk();

        $this->assertSame(2, SecurityAlert::where('account_id', $user->id)->count());
    }

    // ----------------------------------------------------------------- mobile

    public function test_verify_current_mobile(): void
    {
        $user = $this->makeUser(['phone_verified_at' => null]);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->as($token, 'POST', '/api/account/mobile/send-code')
            ->assertOk()
            ->assertJsonPath('data.expires_in', 120)
            ->assertJsonPath('data.resend_in', 60)
            ->assertJsonPath('data.remaining_attempts', 4);

        $this->as($token, 'POST', '/api/account/mobile/verify', ['code' => '000000'])->assertStatus(422)->assertJsonPath('error_code', 'INVALID_CODE');
        $this->as($token, 'POST', '/api/account/mobile/verify', ['code' => $this->sms->lastCodeFor($user->phone)])->assertOk();

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_change_mobile_rejects_taken_number_and_records_event(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $token = $this->token($user);

        $this->as($token, 'POST', '/api/account/mobile/send-code', ['mobile' => $other->phone])
            ->assertStatus(422)->assertJsonPath('error_code', 'MOBILE_TAKEN');

        $this->as($token, 'POST', '/api/account/mobile/send-code', ['mobile' => '09121234567'])->assertOk();
        $code = $this->sms->lastCodeFor('09121234567');

        // کد برای شماره‌ی دیگری قابل استفاده نیست
        $this->as($token, 'POST', '/api/account/mobile/verify', ['mobile' => '09127654321', 'code' => $code])->assertStatus(422);
        $this->as($token, 'POST', '/api/account/mobile/send-code', ['mobile' => '09121234567'])->assertStatus(429);

        $this->travel(61)->seconds();
        $this->as($token, 'POST', '/api/account/mobile/send-code', ['mobile' => '09121234567'])->assertOk();
        $this->as($token, 'POST', '/api/account/mobile/verify', [
            'mobile' => '09121234567', 'code' => $this->sms->lastCodeFor('09121234567'),
        ])->assertOk()->assertJsonPath('data.mobile', '09121234567');

        $this->assertSame('09121234567', $user->fresh()->phone);
        $this->assertContains('mobile_changed', AccountActivity::where('account_id', $user->id)->pluck('type')->all());
        $this->assertSame('critical', SecurityAlert::where('type', 'mobile_changed')->value('severity'));
    }

    public function test_code_requests_are_limited_to_five_per_hour(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('auth-token')->plainTextToken;

        for ($i = 1; $i <= 5; $i++) {
            $this->as($token, 'POST', '/api/account/mobile/send-code')->assertOk()->assertJsonPath('data.remaining_attempts', 5 - $i);
            $this->travel(61)->seconds();
        }

        $this->as($token, 'POST', '/api/account/mobile/send-code')
            ->assertStatus(429)->assertJsonPath('error_code', 'TOO_MANY_REQUESTS');
    }

    // ----------------------------------------------------------------- two-factor

    public function test_sms_two_factor_enable_login_recovery_and_disable(): void
    {
        $user = $this->makeUser();
        $token = $this->token($user);

        $this->as($token, 'POST', '/api/account/security/two-factor/send-code', ['purpose' => 'enable', 'method' => 'sms'])
            ->assertOk()->assertJsonPath('data.expires_in', 120);
        $codes = $this->as($token, 'POST', '/api/account/security/two-factor/enable', [
            'method' => 'sms', 'code' => $this->sms->lastCodeFor($user->phone),
        ])->assertOk()->json('data.recovery_codes');

        $this->assertCount(8, $codes);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $codes[0]);
        $this->as($token, 'GET', '/api/account/security')
            ->assertJsonPath('data.two_factor.enabled', true)
            ->assertJsonPath('data.two_factor.method', 'sms')
            ->assertJsonPath('data.two_factor.verified_mobile', $user->phone);

        // ورود: به‌جای توکن، مرحله‌ی دوم
        $login = $this->login($user)->assertOk()
            ->assertJsonPath('requires_two_factor', true)
            ->assertJsonPath('method', 'sms')
            ->assertJsonMissingPath('data.token');
        $challenge = $login->json('two_factor_token');

        $this->postJson('/api/auth/two-factor/verify', ['two_factor_token' => $challenge, 'code' => '000000'])
            ->assertStatus(422)->assertJsonPath('error_code', 'INVALID_CODE');
        $this->postJson('/api/auth/two-factor/verify', [
            'two_factor_token' => $challenge, 'code' => $this->sms->lastCodeFor($user->phone),
        ])->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['token', 'token_type']]);

        // توکن مرحله‌ی دوم یک‌بارمصرف است
        $this->postJson('/api/auth/two-factor/verify', ['two_factor_token' => $challenge, 'code' => '123456'])
            ->assertStatus(422)->assertJsonPath('error_code', 'TWO_FACTOR_EXPIRED');

        // کد بازیابی هم پذیرفته می‌شود و فقط یک بار
        $this->travel(61)->seconds();
        $challenge = $this->login($user)->json('two_factor_token');
        $this->postJson('/api/auth/two-factor/verify', ['two_factor_token' => $challenge, 'code' => strtolower($codes[0])])->assertOk();
        $this->travel(61)->seconds();
        $challenge = $this->login($user)->json('two_factor_token');
        $this->postJson('/api/auth/two-factor/verify', ['two_factor_token' => $challenge, 'code' => $codes[0]])->assertStatus(422);

        // ساخت کدهای بازیابی جدید، کدهای قبلی را باطل می‌کند
        $this->travel(61)->seconds();
        $this->as($token, 'POST', '/api/account/security/two-factor/send-code', ['purpose' => 'recovery_codes', 'method' => 'sms'])->assertOk();
        $newCodes = $this->as($token, 'POST', '/api/account/security/two-factor/recovery-codes', [
            'code' => $this->sms->lastCodeFor($user->phone),
        ])->assertOk()->json('data.recovery_codes');
        $this->assertCount(8, $newCodes);
        $this->travel(61)->seconds();
        $challenge = $this->login($user)->json('two_factor_token');
        $this->postJson('/api/auth/two-factor/verify', ['two_factor_token' => $challenge, 'code' => $codes[1]])->assertStatus(422);

        // غیرفعال‌سازی
        $this->travel(61)->seconds();
        $this->as($token, 'POST', '/api/account/security/two-factor/send-code', ['purpose' => 'disable', 'method' => 'sms'])->assertOk();
        $this->as($token, 'POST', '/api/account/security/two-factor/disable', ['code' => $this->sms->lastCodeFor($user->phone)])->assertOk();
        $this->login($user)->assertOk()->assertJsonStructure(['data' => ['token']]);

        $types = AccountActivity::where('account_id', $user->id)->pluck('type')->all();
        $this->assertContains('two_factor_enabled', $types);
        $this->assertContains('two_factor_disabled', $types);
        $this->assertSame(2, SecurityAlert::where('type', 'two_factor_changed')->count());
    }

    public function test_authenticator_app_two_factor(): void
    {
        $user = $this->makeUser();
        $token = $this->token($user);

        $setup = $this->as($token, 'POST', '/api/account/security/two-factor/send-code', ['purpose' => 'enable', 'method' => 'app'])
            ->assertOk()->json('data');
        $this->assertStringStartsWith('otpauth://totp/', $setup['otpauth_url']);

        $this->as($token, 'POST', '/api/account/security/two-factor/enable', ['method' => 'app', 'code' => '000000'])->assertStatus(422);
        $this->as($token, 'POST', '/api/account/security/two-factor/enable', [
            'method' => 'app', 'code' => Totp::code($setup['secret']),
        ])->assertOk();

        $smsBefore = count($this->sms->sent);
        $challenge = $this->login($user)->assertJsonPath('method', 'app')->json('two_factor_token');
        $this->assertCount($smsBefore, $this->sms->sent, 'روش app نباید پیامک بفرستد');

        $this->travel(31)->seconds();
        $code = Totp::code($setup['secret']);
        $this->postJson('/api/auth/two-factor/verify', ['two_factor_token' => $challenge, 'code' => $code])->assertOk();

        // همان کد دوباره پذیرفته نمی‌شود
        $challenge = $this->login($user)->json('two_factor_token');
        $this->postJson('/api/auth/two-factor/verify', ['two_factor_token' => $challenge, 'code' => $code])->assertStatus(422);
    }

    public function test_organization_login_with_two_factor_returns_organization_payload(): void
    {
        $user = $this->makeUser(['account_type' => 'organization']);
        Organization::factory()->create(['user_id' => $user->id, 'organization_code' => '654321', 'organization_name' => 'شرکت نمونه']);
        $token = $user->createToken('organization-token')->plainTextToken;
        $this->as($token, 'POST', '/api/account/security/two-factor/send-code', ['purpose' => 'enable', 'method' => 'sms']);
        $this->as($token, 'POST', '/api/account/security/two-factor/enable', ['method' => 'sms', 'code' => $this->sms->lastCodeFor($user->phone)])->assertOk();

        $this->travel(61)->seconds();
        $this->app['auth']->forgetGuards();
        $login = $this->postJson('/api/organization/login', ['organization_code' => '654321', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('requires_two_factor', true);

        $this->postJson('/api/auth/two-factor/verify', [
            'two_factor_token' => $login->json('two_factor_token'), 'code' => $this->sms->lastCodeFor($user->phone),
        ])->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.organization.organization_name', 'شرکت نمونه')
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'account_type']]]);
    }

    public function test_totp_matches_rfc6238_vector(): void
    {
        // secret = "12345678901234567890"، زمان 59 ← 94287082 (۸ رقم) ← ۶ رقم آخر
        $this->assertSame('287082', Totp::code('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 59));
        $this->assertTrue(Totp::verify('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '287082', 1, 59));
    }

    // ----------------------------------------------------------------- deletion

    public function test_individual_deletion_is_approved_with_grace_period_then_anonymized(): void
    {
        $user = $this->makeUser(['melicode' => '0012345678', 'card_number' => '6037990000000000']);
        $order = $this->makeOrder($user, ['status' => 2]);
        $token = $this->token($user);

        $this->as($token, 'GET', '/api/account/deletion-request')->assertOk()->assertJsonPath('data', null);
        $this->as($token, 'POST', '/api/account/deletion-request/send-code')->assertOk();
        $this->as($token, 'POST', '/api/account/deletion-request', ['code' => '111111'])->assertStatus(422);
        $created = $this->as($token, 'POST', '/api/account/deletion-request', [
            'code' => $this->sms->lastCodeFor($user->phone), 'reason' => 'دیگر استفاده نمی‌کنم',
        ])->assertOk()->assertJsonPath('data.status', 'approved')->json('data');
        $this->assertNotNull($created['scheduled_at']);

        // انصراف و ثبت دوباره
        $this->as($token, 'POST', '/api/account/deletion-request/cancel')->assertOk()->assertJsonPath('data.status', 'canceled');
        $this->travel(61)->seconds();
        $this->as($token, 'POST', '/api/account/deletion-request/send-code')->assertOk();
        $this->as($token, 'POST', '/api/account/deletion-request', ['code' => $this->sms->lastCodeFor($user->phone)])->assertOk();

        // قبل از پایان مهلت اجرا نمی‌شود
        $this->assertSame(0, app(AccountDeletionService::class)->processDue());
        $this->travel(15)->days();
        $this->artisan('accounts:process-deletions')->assertSuccessful();

        $fresh = $user->fresh();
        $this->assertSame('کاربر حذف‌شده', $fresh->name);
        $this->assertNull($fresh->melicode);
        $this->assertNull($fresh->card_number);
        $this->assertFalse($fresh->hasAccess());
        $this->assertSame(0, $fresh->tokens()->count());
        $this->assertNotNull($order->fresh(), 'سوابق سفارش نباید حذف شود');
        $this->assertSame('done', AccountDeletionRequest::latest('id')->value('status'));
        $this->login($user)->assertUnauthorized();
    }

    public function test_organization_deletion_waits_for_admin_approval(): void
    {
        $user = $this->makeUser(['account_type' => 'company']);
        Organization::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('organization-token')->plainTextToken;

        $this->as($token, 'POST', '/api/account/deletion-request/send-code')->assertOk();
        $this->as($token, 'POST', '/api/account/deletion-request', ['code' => $this->sms->lastCodeFor($user->phone)])
            ->assertOk()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.scheduled_at', null);
        $this->as($token, 'POST', '/api/account/deletion-request/send-code')->assertStatus(429); // ارسال مجدد زود

        $this->travel(30)->days();
        app(AccountDeletionService::class)->processDue();
        $this->assertSame('pending', AccountDeletionRequest::sole()->status);

        app(AccountDeletionService::class)->approveByAdmin(AccountDeletionRequest::sole(), null, 'تأیید شد');
        $this->assertSame('done', AccountDeletionRequest::sole()->status);
        $this->assertSame('کاربر حذف‌شده', $user->fresh()->name);
    }
}
