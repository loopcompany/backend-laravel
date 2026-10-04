<?php

namespace Tests\Feature;

use App\Models\FirebaseDeviceToken;
use App\Models\Technician;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

/**
 * چک‌لیست تست سند «Backend-Account-Security» (امنیت حساب اپ تکنسین).
 */
class TechnicianAccountSecurityTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    private function technician(): Technician
    {
        return $this->makeTechnician(['referral_code' => '123456', 'password' => 'secret123', 'phone_verified_at' => now()]);
    }

    private function login(array $device = [], string $ip = '5.120.33.10'): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/technician/login', ['referral_code' => '123456', 'password' => 'secret123'] + $device);
    }

    private function phone(string $deviceId, string $model = 'SM-A225F'): array
    {
        return [
            'device_id' => $deviceId, 'platform' => 'android', 'device_type' => 'phone', 'device_brand' => 'samsung',
            'device_model' => $model, 'os_name' => 'Android', 'os_version' => '13', 'app_version' => '1.0.0',
        ];
    }

    private function as(string $token, string $method, string $uri, array $data = [], string $ip = '5.120.33.10'): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->withToken($token)->json($method, $uri, $data);
    }

    public function test_login_stores_device_columns_on_the_new_token(): void
    {
        $tech = $this->technician();

        $this->login($this->phone('android-aaa'))->assertOk();

        $token = $tech->tokens()->sole();
        $this->assertSame('android-aaa', $token->device_id);
        $this->assertSame('samsung', $token->device_brand);
        $this->assertSame('SM-A225F', $token->device_model);
        $this->assertSame('13', $token->os_version);
        $this->assertSame('5.120.33.10', $token->ip_address);
        $this->assertSame('5.120.33.10', $token->last_ip);
    }

    public function test_relogin_on_same_device_keeps_only_one_token(): void
    {
        $tech = $this->technician();

        $this->login($this->phone('android-aaa'))->assertOk();
        $this->login($this->phone('android-aaa'))->assertOk();
        $this->login($this->phone('android-bbb'))->assertOk();

        $this->assertSame(1, $tech->tokens()->where('device_id', 'android-aaa')->count());
        $this->assertSame(2, $tech->tokens()->count());
    }

    public function test_old_app_without_device_fields_still_logs_in(): void
    {
        $tech = $this->technician();

        $this->login()->assertOk()->assertJsonPath('success', true);
        $this->assertNull($tech->tokens()->sole()->device_id);
    }

    public function test_devices_lists_each_phone_with_correct_is_current(): void
    {
        $this->technician();
        $a = $this->login($this->phone('android-aaa'))->json('data.token');
        $b = $this->login($this->phone('android-bbb', 'SM-S918B'))->json('data.token');

        $devices = collect($this->as($a, 'GET', '/api/technician/security/devices')
            ->assertOk()->assertJsonPath('data.total', 2)->json('data.devices'))->keyBy('id');

        $this->assertTrue($devices['android-aaa']['is_current']);
        $this->assertFalse($devices['android-bbb']['is_current']);
        $this->assertSame('SM-S918B', $devices['android-bbb']['device_model']);
        $this->assertSame(1, $devices['android-aaa']['sessions_count']);
        foreach (['platform', 'device_type', 'os_name', 'os_version', 'app_version', 'last_ip', 'location', 'last_active_at'] as $key) {
            $this->assertArrayHasKey($key, $devices['android-aaa']);
        }

        $fromB = collect($this->as($b, 'GET', '/api/technician/security/devices')->json('data.devices'))->keyBy('id');
        $this->assertTrue($fromB['android-bbb']['is_current']);
    }

    public function test_logout_device_revokes_its_tokens_and_fcm_row(): void
    {
        $tech = $this->technician();
        $a = $this->login($this->phone('android-aaa'))->json('data.token');
        $b = $this->login($this->phone('android-bbb'))->json('data.token');
        foreach (['android-aaa', 'android-bbb'] as $deviceId) {
            FirebaseDeviceToken::create([
                'tokenable_type' => Technician::class, 'tokenable_id' => $tech->id, 'token' => "fcm-{$deviceId}",
                'token_hash' => hash('sha256', "fcm-{$deviceId}"), 'platform' => 'android', 'device_id' => $deviceId,
            ]);
        }

        $this->as($a, 'DELETE', '/api/technician/security/devices/android-bbb')->assertOk()->assertJsonPath('success', true);

        $this->as($b, 'GET', '/api/technician/security/devices')->assertUnauthorized();
        $this->as($a, 'GET', '/api/technician/security/devices')->assertOk();
        $this->assertDatabaseMissing('firebase_device_tokens', ['device_id' => 'android-bbb']);
        $this->assertDatabaseHas('firebase_device_tokens', ['device_id' => 'android-aaa']);
    }

    public function test_logout_others_keeps_only_the_current_token(): void
    {
        $tech = $this->technician();
        $this->login($this->phone('android-aaa'));
        $this->login($this->phone('android-bbb'));
        $this->login(); // نشست قدیمی بدون device_id
        $current = $this->login($this->phone('android-ccc'))->json('data.token');

        $this->as($current, 'POST', '/api/technician/security/logout-others')
            ->assertOk()
            ->assertJsonPath('data.revoked_sessions', 3)
            ->assertJsonPath('data.revoked_devices', 3);

        $this->assertSame(['android-ccc'], $tech->tokens()->pluck('device_id')->all());
    }

    public function test_current_device_and_session_cannot_be_removed_through_these_endpoints(): void
    {
        $tech = $this->technician();
        $a = $this->login($this->phone('android-aaa'))->json('data.token');
        $currentId = $tech->tokens()->sole()->id;

        $this->as($a, 'DELETE', '/api/technician/security/devices/android-aaa')
            ->assertStatus(422)->assertJsonPath('error_code', 'CURRENT_DEVICE');
        $this->as($a, 'DELETE', "/api/technician/security/sessions/{$currentId}")
            ->assertStatus(422)->assertJsonPath('error_code', 'CURRENT_SESSION');
        $this->as($a, 'DELETE', '/api/technician/security/devices/does-not-exist')
            ->assertNotFound()->assertJsonPath('error_code', 'DEVICE_NOT_FOUND');
    }

    public function test_ending_another_technicians_session_returns_404_without_deleting(): void
    {
        $this->technician();
        $mine = $this->login($this->phone('android-aaa'))->json('data.token');
        $other = $this->makeTechnician();
        $othersTokenId = $other->createToken('technician-app')->accessToken->id;

        $this->as($mine, 'DELETE', "/api/technician/security/sessions/{$othersTokenId}")
            ->assertNotFound()->assertJsonPath('error_code', 'SESSION_NOT_FOUND');
        $this->assertNotNull(PersonalAccessToken::find($othersTokenId));
    }

    public function test_sessions_endpoint_and_ending_a_session(): void
    {
        $tech = $this->technician();
        $a = $this->login($this->phone('android-aaa'))->json('data.token');
        $this->login($this->phone('android-bbb'));
        $otherId = $tech->tokens()->where('device_id', 'android-bbb')->value('id');

        $sessions = collect($this->as($a, 'GET', '/api/technician/security/sessions')->assertOk()->json('data.sessions'));
        $this->assertCount(2, $sessions);
        $this->assertSame('active', $sessions->firstWhere('id', $otherId)['status']);
        $this->assertTrue($sessions->firstWhere('device_id', 'android-aaa')['is_current']);
        $this->assertNull($sessions->first()['expires_at']);

        $this->as($a, 'DELETE', "/api/technician/security/sessions/{$otherId}")->assertOk();
        $this->assertNull(PersonalAccessToken::find($otherId));
    }

    public function test_pre_migration_token_is_filled_by_current_device_and_last_ip_tracks_requests(): void
    {
        $tech = $this->technician();
        $legacy = $tech->createToken('technician-app')->plainTextToken; // قبل از migration: بدون اطلاعات دستگاه

        $this->as($legacy, 'PUT', '/api/technician/security/current-device', $this->phone('android-old'), '10.0.0.1')->assertOk();
        $token = $tech->tokens()->sole();
        $this->assertSame('android-old', $token->device_id);
        $this->assertSame('10.0.0.1', $token->ip_address);

        // درخواست بعدی از IP دیگر، «IP آخرین اتصال» را عوض می‌کند ولی IP ورود ثابت می‌ماند
        $this->as($legacy, 'GET', '/api/technician/security/devices', [], '10.0.0.2')
            ->assertOk()->assertJsonPath('data.devices.0.last_ip', '10.0.0.2');
        $this->assertSame('10.0.0.1', $token->fresh()->ip_address);

        $this->as($legacy, 'PUT', '/api/technician/security/current-device', ['platform' => 'symbian'])->assertStatus(422);
    }
}
