<?php
namespace Tests\Feature;

use App\Services\TechnicianIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

class TechnicianIdentityTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    public function test_card_verification_exposes_only_public_identity_and_revocation_is_immediate(): void
    {
        config(['app.url' => 'https://loop.test']);
        $service = app(TechnicianIdentityService::class);
        $tech = $service->issue($this->makeTechnician(['referral_code' => '2118054110000001']));
        Sanctum::actingAs($this->makeUser());
        $url = rtrim(config('app.url'), '/').'/technician/identity/'.$tech->identity_badge_uuid;
        $response = $this->postJson('/api/technicians/identity/verify', ['qr' => $url])->assertOk()->assertJsonPath('verified', true);
        $this->assertSame(['verified', 'name', 'personnel_code', 'technician_type'], array_keys($response->json('data')));
        $old = $tech->identity_badge_uuid; $tech = $service->issue($tech, true);
        $this->assertNull($service->verify($old));
        $this->assertNotNull($service->verify($tech->identity_badge_uuid));
        $tech->update(['has_access' => false]);
        $this->assertNull($service->verify($tech->identity_badge_uuid));
    }

    public function test_external_links_and_unapproved_accounts_never_receive_a_green_verification(): void
    {
        $service = app(TechnicianIdentityService::class);
        $tech = $service->issue($this->makeTechnician(['referral_code' => '2118054110000002']));
        $this->assertNull($service->verify('https://evil.example/technician/identity/'.$tech->identity_badge_uuid));
        $tech->update(['approval_status' => 'rejected']);
        $this->assertNull($service->verify($tech->identity_badge_uuid));
    }

    public function test_download_requires_real_admin_permissions_and_svg_contains_branded_code(): void
    {
        $tech = $this->makeTechnician(['referral_code' => '2118054110000003']);
        $this->get('/admin/technician-identity/'.$tech->id.'/download')->assertRedirect();
        $svg = app(TechnicianIdentityService::class)->card($tech);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('2118054110000003', $svg);
        $this->assertStringContainsString('data:image/svg+xml;base64,', $svg);
    }
}
