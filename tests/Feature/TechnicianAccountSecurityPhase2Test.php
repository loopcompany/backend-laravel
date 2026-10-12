<?php

namespace Tests\Feature;

use App\Models\DeviceMarketingName;
use App\Services\Security\GeoIpLocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

/**
 * چک‌لیست سند «Backend-Account-Security-Phase2»: device_name، location و پیام‌های فارسی خروج.
 */
class TechnicianAccountSecurityPhase2Test extends TestCase
{
    use RefreshDatabase, MakesPeople;

    protected function setUp(): void
    {
        parent::setUp();

        // GeoIP محلی در تست: فقط IP عمومی ایرانی شناخته می‌شود
        $this->app->instance(GeoIpLocator::class, new class extends GeoIpLocator {
            public function locate(?string $ip): ?string
            {
                return $ip === '5.120.33.10' ? 'تهران، ایران' : null;
            }
        });

        $this->makeTechnician(['referral_code' => '123456', 'password' => 'secret123', 'phone_verified_at' => now()]);
    }

    private function login(string $model = 'SM-A225F', string $ip = '5.120.33.10', string $deviceId = 'android-1'): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->postJson('/api/technician/login', [
            'referral_code' => '123456', 'password' => 'secret123',
            'device_id' => $deviceId, 'platform' => 'android', 'device_type' => 'phone', 'device_brand' => 'samsung',
            'device_model' => $model, 'os_name' => 'Android', 'os_version' => '13', 'app_version' => '1.0.0',
        ]);
    }

    private function as(string $token, string $method, string $uri, array $data = [], string $ip = '5.120.33.10'): TestResponse
    {
        $this->app['auth']->forgetGuards();

        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->withToken($token)->json($method, $uri, $data);
    }

    public function test_device_name_and_location_in_devices_and_sessions(): void
    {
        $token = $this->login()->assertOk()->json('data.token');

        $this->as($token, 'GET', '/api/technician/security/devices')->assertOk()
            ->assertJsonPath('data.devices.0.device_model', 'SM-A225F')
            ->assertJsonPath('data.devices.0.device_name', 'Galaxy A22')
            ->assertJsonPath('data.devices.0.location', 'تهران، ایران');

        $this->as($token, 'GET', '/api/technician/security/sessions')->assertOk()
            ->assertJsonPath('data.sessions.0.device_name', 'Galaxy A22');
    }

    public function test_unknown_model_and_private_ip_return_null(): void
    {
        $token = $this->login('SM-X9999Z', '192.168.1.20')->assertOk()->json('data.token');

        $this->as($token, 'GET', '/api/technician/security/devices', [], '192.168.1.20')->assertOk()
            ->assertJsonPath('data.devices.0.device_model', 'SM-X9999Z')
            ->assertJsonPath('data.devices.0.device_name', null)
            ->assertJsonPath('data.devices.0.location', null);
    }

    public function test_imported_names_win_and_existing_sessions_get_filled_by_current_device(): void
    {
        DeviceMarketingName::insert([
            ['model' => 'M2101K7AG', 'brand' => 'Redmi', 'marketing_name' => 'Redmi Note 10'],
            ['model' => 'SM-A225F', 'brand' => 'Samsung', 'marketing_name' => 'Galaxy A22 (imported)'],
        ]);

        $token = $this->login('SM-A225F')->json('data.token');
        // نشست قدیمی، پیش از این تغییر: بدون device_name
        PersonalAccessToken::query()->update(['device_name' => null, 'device_model' => 'M2101K7AG', 'device_brand' => 'Xiaomi']);

        $this->as($token, 'PUT', '/api/technician/security/current-device', [
            'device_brand' => 'Xiaomi', 'device_model' => 'M2101K7AG',
        ])->assertOk();

        $this->as($token, 'GET', '/api/technician/security/devices')
            ->assertJsonPath('data.devices.0.device_name', 'Redmi Note 10');

        $other = $this->login('SM-A225F', deviceId: 'android-2')->json('data.token');
        $this->as($other, 'GET', '/api/technician/security/sessions')->assertOk();
        $this->assertSame('Galaxy A22 (imported)', PersonalAccessToken::where('device_id', 'android-2')->value('device_name'));
    }

    public function test_location_is_recomputed_only_when_ip_changes(): void
    {
        $token = $this->login('SM-A225F', '192.168.1.20')->json('data.token');
        $this->assertNull(PersonalAccessToken::value('last_location'));

        $this->as($token, 'GET', '/api/technician/security/devices')
            ->assertJsonPath('data.devices.0.last_ip', '5.120.33.10')
            ->assertJsonPath('data.devices.0.location', 'تهران، ایران');
    }

    public function test_logout_messages_are_persian(): void
    {
        $token = $this->login()->json('data.token');
        $this->as($token, 'POST', '/api/technician/logout')->assertOk()
            ->assertJsonPath('message', 'خروج با موفقیت انجام شد.');

        $token = $this->login()->json('data.token');
        $this->as($token, 'POST', '/api/technician/logout-all')->assertOk()
            ->assertJsonPath('message', 'خروج از همه دستگاه‌ها با موفقیت انجام شد.');
    }

    public function test_import_command_reads_the_utf16_google_play_csv(): void
    {
        $csv = "Retail Branding,Marketing Name,Device,Model\n"
            . "Samsung,Galaxy A22,a22,SM-A225F\n"
            . "Samsung,Galaxy A22 dup,a22,SM-A225F\n"
            . "Xiaomi,Redmi Note 11,spes,2201117TG\n";
        $path = storage_path('framework/testing-devices.csv');
        @mkdir(dirname($path), 0775, true);
        file_put_contents($path, "\xFF\xFE" . mb_convert_encoding($csv, 'UTF-16LE', 'UTF-8'));

        $this->artisan('devices:import-names', ['--file' => $path])->assertSuccessful();
        @unlink($path);

        $this->assertSame(2, DeviceMarketingName::count());
        $this->assertSame('Redmi Note 11', DeviceMarketingName::where('model', '2201117TG')->value('marketing_name'));
        $this->assertSame('Galaxy A22', DeviceMarketingName::where('model', 'SM-A225F')->value('marketing_name'));
    }
}
