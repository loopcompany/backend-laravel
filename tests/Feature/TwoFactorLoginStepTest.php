<?php

namespace Tests\Feature;

use App\Models\AccountSecuritySetting;
use App\Models\Organization;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Concerns\MakesPeople;
use Tests\Fakes\FakeSmsService;
use Tests\TestCase;

/**
 * سند اپ کاربر، بخش ۱۴: مرحله‌ی دوم ورود برای حساب سازمانی، ارسال مجدد، مهلت ۵ دقیقه و 429.
 */
class TwoFactorLoginStepTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    private FakeSmsService $sms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sms = new FakeSmsService();
        $this->app->instance(SmsService::class, $this->sms);
        $push = Mockery::mock(FirebaseNotificationService::class);
        $push->shouldReceive('sendToUser')->andReturn(['sent' => 0, 'failed' => 0, 'removed' => 0]);
        $this->app->instance(FirebaseNotificationService::class, $push);
    }

    private function organizationWithTwoFactor(): User
    {
        $user = $this->makeUser(['account_type' => 'company', 'has_access' => 1]);
        Organization::factory()->create([
            'user_id' => $user->id, 'organization_code' => '123456',
            'profile_status' => 'approved', 'contract_status' => 'approved',
        ]);
        AccountSecuritySetting::create([
            'account_type' => $user->getMorphClass(), 'account_id' => $user->id,
            'two_factor_enabled' => true, 'two_factor_method' => 'sms',
        ]);

        return $user;
    }

    private function orgLogin(): string
    {
        return $this->postJson('/api/organization/login', ['organization_code' => '123456', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('requires_two_factor', true)
            ->assertJsonPath('method', 'sms')
            ->json('two_factor_token');
    }

    public function test_organization_verify_returns_the_organization_login_shape(): void
    {
        $user = $this->organizationWithTwoFactor();
        $challenge = $this->orgLogin();

        $this->postJson('/api/organization/two-factor/verify', ['two_factor_token' => $challenge, 'code' => '000000'])
            ->assertStatus(422)->assertJsonPath('error_code', 'INVALID_CODE');

        $this->postJson('/api/organization/two-factor/verify', [
            'two_factor_token' => $challenge, 'code' => $this->sms->lastCodeFor($user->phone),
        ])->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['token', 'user', 'organization']]);

        // ورود ناموفق (کد اشتباه) و ورود موفق هر دو در فعالیت‌های اخیر
        $this->assertDatabaseHas('account_activities', ['account_id' => $user->id, 'type' => 'login_failed']);
        $this->assertDatabaseHas('account_activities', ['account_id' => $user->id, 'type' => 'login_success']);
    }

    public function test_resend_waits_60_seconds_and_challenge_expires_after_5_minutes(): void
    {
        $user = $this->organizationWithTwoFactor();
        $challenge = $this->orgLogin();
        $first = $this->sms->lastCodeFor($user->phone);

        $this->postJson('/api/organization/two-factor/resend', ['two_factor_token' => $challenge])
            ->assertStatus(429)->assertJsonPath('error_code', 'RESEND_TOO_SOON');

        $this->travel(61)->seconds();
        $this->postJson('/api/auth/two-factor/resend', ['two_factor_token' => $challenge])
            ->assertOk()->assertJsonPath('data.resend_in', 60);
        $this->assertNotNull($this->sms->lastCodeFor($user->phone));
        $this->assertCount(2, $this->sms->sent);

        $this->travel(5)->minutes();
        $this->postJson('/api/organization/two-factor/verify', ['two_factor_token' => $challenge, 'code' => $first])
            ->assertStatus(422)->assertJsonPath('error_code', 'TWO_FACTOR_EXPIRED');
        $this->postJson('/api/organization/two-factor/resend', ['two_factor_token' => $challenge])
            ->assertStatus(422)->assertJsonPath('error_code', 'TWO_FACTOR_EXPIRED');
    }

    public function test_too_many_wrong_codes_is_429_not_401(): void
    {
        $this->organizationWithTwoFactor();
        $challenge = $this->orgLogin();

        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/organization/two-factor/verify', ['two_factor_token' => $challenge, 'code' => '000000'])
                ->assertStatus(422);
        }

        $this->postJson('/api/organization/two-factor/verify', ['two_factor_token' => $challenge, 'code' => '000000'])
            ->assertStatus(429)->assertJsonPath('error_code', 'TWO_FACTOR_LOCKED');
    }
}
