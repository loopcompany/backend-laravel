<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationEditRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

/**
 * سند اپ کاربر، بخش ۱۱ (ویرایش / درخواست ویرایش) و ۱۲ (حذف عکس پروفایل).
 */
class OrganizationEditRequestTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    private function organizationUser(): User
    {
        $provinceId = DB::table('provinces')->insertGetId(['title' => 'تهران', 'created_at' => now(), 'updated_at' => now()]);
        $cityId = DB::table('cities')->insertGetId(['province_id' => $provinceId, 'title' => 'تهران', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('regions')->insert([
            ['city_id' => $cityId, 'title' => 'منطقه ۵', 'code' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['city_id' => $cityId, 'title' => 'منطقه ۷', 'code' => 7, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $user = $this->makeUser(['account_type' => 'company', 'melicode' => '10101234567', 'region' => '5']);
        Organization::factory()->create([
            'user_id' => $user->id,
            'organization_name' => 'شرکت آزمون',
            'manager_full_name' => 'مدیر قدیم',
            'agent_name' => 'نماینده قدیم',
            'profile_status' => 'approved',
            'contract_status' => 'approved',
        ]);

        return $user->fresh();
    }

    public function test_free_fields_apply_immediately_and_partially(): void
    {
        $user = $this->organizationUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/organization/update-profile', [
            'agent_name' => 'نماینده جدید',
            'history' => 'ده سال',
            'organization_email' => 'info@test.local',
            'region' => '7',
            // فیلد قفل بدون تغییر (فرم کامل اپ قدیمی) خطا نیست
            'organization_name' => 'شرکت آزمون',
        ])->assertOk()
            ->assertJsonPath('data.agent_name', 'نماینده جدید')
            ->assertJsonPath('data.organization_email', 'info@test.local')
            ->assertJsonPath('data.locked_fields_unlocked', false)
            ->assertJsonPath('data.edit_request', null);

        $organization = $user->organization->fresh();
        $this->assertSame('ده سال', $organization->history);
        // فیلدی که نیامده خالی نمی‌شود
        $this->assertSame('مدیر قدیم', $organization->manager_full_name);
        $this->assertSame('7', (string) $user->fresh()->region);
    }

    public function test_locked_fields_need_an_approved_edit_request_and_relock_after_use(): void
    {
        Storage::fake('local');
        $user = $this->organizationUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/organization/update-profile', ['manager_full_name' => 'مدیر جدید'])
            ->assertStatus(403)->assertJsonPath('error_code', 'FIELDS_LOCKED');

        $this->post('/api/organization/edit-requests', ['description' => 'تغییر مدیرعامل'], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('letter');

        $request = $this->post('/api/organization/edit-requests', [
            'letter' => UploadedFile::fake()->create('نامه.pdf', 300, 'application/pdf'),
            'description' => 'درخواست تغییر اطلاعات حساب کاربری',
            'fields' => ['manager_full_name', 'organization_name'],
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->json('data');

        // نامه با لینک امضاشده باز می‌شود
        $this->get(parse_url($request['letter_url'], PHP_URL_PATH) . '?' . parse_url($request['letter_url'], PHP_URL_QUERY))->assertOk();

        // درخواست در جریان ← 409
        $this->post('/api/organization/edit-requests', [
            'letter' => UploadedFile::fake()->create('n.pdf', 10, 'application/pdf'), 'description' => 'دوباره',
        ], ['Accept' => 'application/json'])->assertStatus(409);

        // تا تأیید پنل هنوز قفل است
        $this->getJson('/api/organization/profile')->assertJsonPath('data.edit_request.status', 'pending')
            ->assertJsonPath('data.locked_fields_unlocked', false);

        // تأیید پنل فقط برای «نام مدیرعامل»
        OrganizationEditRequest::sole()->update(['status' => 'approved', 'unlocked_fields' => ['manager_full_name'], 'reviewed_at' => now()]);

        $this->getJson('/api/organization/profile')->assertOk()
            ->assertJsonPath('data.edit_request.status', 'approved')
            ->assertJsonPath('data.locked_fields_unlocked', true)
            ->assertJsonPath('data.unlocked_fields', ['manager_full_name']);

        $this->postJson('/api/organization/update-profile', ['organization_name' => 'نام دیگر'])
            ->assertStatus(403)->assertJsonPath('locked_fields', ['organization_name']);

        $this->postJson('/api/organization/update-profile', ['manager_full_name' => 'مدیر جدید'])->assertOk()
            ->assertJsonPath('data.manager_full_name', 'مدیر جدید')
            ->assertJsonPath('data.edit_request.status', 'used')
            ->assertJsonPath('data.locked_fields_unlocked', false);

        // پس از یک بار ذخیره، دوباره قفل
        $this->postJson('/api/organization/update-profile', ['manager_full_name' => 'باز هم جدید'])->assertStatus(403);
    }

    public function test_manager_mobile_change_must_be_unique(): void
    {
        $user = $this->organizationUser();
        $other = $this->makeUser();
        Sanctum::actingAs($user);
        $user->organization->editRequests()->create([
            'user_id' => $user->id, 'status' => 'approved', 'description' => '-', 'letter_path' => 'x.pdf',
        ]);

        $this->postJson('/api/organization/update-profile', ['manager_mobile' => $other->phone])
            ->assertStatus(422)->assertJsonPath('error_code', 'MOBILE_TAKEN');

        $this->postJson('/api/organization/update-profile', ['manager_mobile' => '09129998877'])->assertOk()
            ->assertJsonPath('data.manager_mobile', '09129998877');
    }

    public function test_profile_photo_removal_for_organization_and_individual(): void
    {
        Storage::fake('public');
        $user = $this->organizationUser();
        Storage::disk('public')->put('images/profiles/org.jpg', 'x');
        $user->forceFill(['profile_photo_path' => 'images/profiles/org.jpg'])->save();
        Sanctum::actingAs($user);

        $this->postJson('/api/organization/update-profile', ['remove_profile_image' => 1])->assertOk()
            ->assertJsonPath('data.profile_image', null);
        Storage::disk('public')->assertMissing('images/profiles/org.jpg');

        $person = $this->makeUser(['profile_photo_path' => 'images/profiles/p.jpg', 'has_access' => 1]);
        Storage::disk('public')->put('images/profiles/p.jpg', 'x');
        Sanctum::actingAs($person);

        $this->putJson('/api/profile', ['remove_profile_photo' => 1])->assertOk()
            ->assertJsonPath('user.profile_photo_path', null);
        $this->assertNull($person->fresh()->profile_photo_path);
        Storage::disk('public')->assertMissing('images/profiles/p.jpg');
    }
}
