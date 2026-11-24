<?php
namespace Tests\Feature;

use App\Models\AppRelease;
use App\Services\AppReleaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AppUpdateCatalogTest extends TestCase
{
    use RefreshDatabase;
    private string $publicRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publicRoot = sys_get_temp_dir().'/loop-release-'.bin2hex(random_bytes(8));
        mkdir($this->publicRoot.'/downloads/android', 0777, true); mkdir($this->publicRoot.'/downloads/windows', 0777, true);
        $this->app->usePublicPath($this->publicRoot);
    }
    protected function tearDown(): void
    {
        foreach (glob($this->publicRoot.'/downloads/*/*') ?: [] as $file) unlink($file);
        rmdir($this->publicRoot.'/downloads/android'); rmdir($this->publicRoot.'/downloads/windows'); rmdir($this->publicRoot.'/downloads'); rmdir($this->publicRoot);
        parent::tearDown();
    }
    private function release(string $name, int $code = 20261124): AppRelease
    {
        $folder = str_ends_with($name, '.apk') ? 'android' : 'windows';
        file_put_contents($this->publicRoot.'/downloads/'.$folder.'/'.$name, str_repeat('test-artifact-', 200));
        return AppRelease::create(['artifact' => $name, 'version_code' => $code, 'is_published' => true]);
    }
    public function test_android_metadata_is_verified_against_local_artifact_and_older_clients_get_an_update(): void
    {
        $release = $this->release('LoopUser-1.0.4.apk');
        $response = $this->getJson('/api/app-updates/check?app=customer&platform=android&version_code=20261123')->assertOk()->assertJsonPath('available', true);
        $this->assertSame($release->sha256, $response->json('release.sha256'));
        $this->assertSame('1.0.4', $response->json('release.version'));
        $this->getJson('/api/app-updates/check?app=customer&platform=android&version_code=20261124')->assertJsonPath('available', false);
        $this->getJson('/api/app-updates/check?app=technician&platform=android&version_code=0')->assertJsonPath('available', false);
        $release->update(['is_published' => false]);
        $this->getJson('/api/app-updates/check?app=customer&platform=android&version_code=0')->assertJsonPath('available', false);
    }
    public function test_windows_variants_are_isolated_and_versions_are_semantic_not_lexicographic(): void
    {
        $this->release('Loop-1.1.9-Windows-10-11-64bit-Setup.exe');
        $this->release('Loop-1.1.10-Windows-10-11-64bit-Setup.exe');
        $this->release('Loop-1.1.8-Windows-7-8-32bit-Setup.exe');
        $this->getJson('/api/app-updates/check?app=desktop&platform=windows&variant=win10-x64&version=1.1.9')->assertJsonPath('available', true)->assertJsonPath('release.version', '1.1.10');
        $this->getJson('/api/app-updates/check?app=desktop&platform=windows&variant=win7-ia32&version=1.1.9')->assertJsonPath('available', false)->assertJsonPath('release.version', '1.1.8');
    }
    public function test_urls_paths_and_missing_artifacts_cannot_be_published(): void
    {
        foreach (['../LoopUser-1.0.4.apk', 'https://evil.example/update.apk', 'LoopUser-9.9.9.apk', 'anything.php'] as $name) {
            try { app(AppReleaseService::class)->inspect($name); $this->fail('Unsafe artifact accepted'); } catch (ValidationException) { $this->addToAssertionCount(1); }
        }
    }
    public function test_published_hash_cannot_silently_change_and_a_missing_artifact_can_still_be_withdrawn(): void
    {
        $release = $this->release('LoopUser-1.0.4.apk'); $file = $this->publicRoot.'/downloads/android/'.$release->artifact;
        file_put_contents($file, str_repeat('changed-', 400));
        try { $release->update(['notes' => 'notes']); $this->fail('Changed binary accepted'); } catch (ValidationException) { $this->addToAssertionCount(1); }
        unlink($file); $release->refresh()->update(['is_published' => false]);
        $this->assertFalse($release->fresh()->is_published);
    }
}
