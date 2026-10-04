<?php

namespace Tests\Feature;

use App\Filament\Resources\CooperationRequestResource;
use App\Filament\Resources\CooperationRequestResource\Pages\EditCooperationRequest;
use App\Filament\Resources\CooperationRequestResource\Pages\ListCooperationRequests;
use App\Filament\Resources\CooperationRequestResource\Pages\ViewCooperationRequest;
use App\Filament\Resources\EmploymentFileResource\Pages\EditEmploymentFile;
use App\Models\Admin;
use App\Models\CooperationRequest;
use App\Models\EmploymentFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * پنل: درخواست‌های همکاری، فرم گزینش و مصاحبه، تاریخچه و پرونده‌ی استخدامی (بخش محرمانه).
 */
class CareersAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions, string $email = 'hr@test.local'): Admin
    {
        $role = Role::create(['name' => 'role-' . md5($email), 'guard_name' => 'admin']);
        foreach (array_merge(['access-admin-panel'], $permissions) as $p) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $p, 'guard_name' => 'admin']));
        }
        $admin = Admin::create(['name' => 'HR ' . $email, 'email' => $email, 'password' => 'password', 'staff_type' => Admin::STAFF_MANAGER]);
        $admin->forceFill(['is_active' => true])->save();
        $admin->assignRole($role);

        return $admin;
    }

    private function cooperation(array $overrides = []): CooperationRequest
    {
        return CooperationRequest::create(array_merge([
            'tracking_code' => CooperationRequest::generateTrackingCode(),
            'status' => 'new', 'source' => 'site',
            'full_name' => 'علی کریمی', 'mobile' => '09121112222', 'national_code' => '0499370899',
            'city' => 'تهران', 'age' => 30, 'gender' => 'male', 'marital_status' => 'married', 'military_status' => 'completed',
            'job_title' => 'field_technician', 'cooperation_type' => 'full_time',
            'education_level' => 'associate', 'work_experience' => '3_5', 'related_experience' => '1_3',
            'skills' => ['hw_parts' => 'advanced'], 'interest_areas' => ['laptop'], 'has_certificates' => true,
            'field_info' => ['has_vehicle' => 'yes', 'vehicle_type' => 'motorcycle', 'has_license' => 'yes', 'license_type' => 'motorcycle', 'mission_range' => 'city', 'carry_equipment' => 'yes', 'onsite_experience' => 'yes'],
            'start_availability' => 'immediately', 'salary_type' => 'negotiable', 'overtime' => 'yes', 'shift_work' => 'coordination',
        ], $overrides));
    }

    public function test_list_view_and_permissions(): void
    {
        $cooperation = $this->cooperation();
        $this->actingAs($this->admin(['view-cooperation-requests']), 'admin');

        Livewire::test(ListCooperationRequests::class)->assertCanSeeTableRecords([$cooperation]);
        $this->get(CooperationRequestResource::getUrl('view', ['record' => $cooperation]))
            ->assertOk()
            ->assertSee($cooperation->tracking_code)
            ->assertSee('اطلاعات ویژه تکنسین میدانی')
            ->assertSee('شناخت قطعات کامپیوتر');

        // بدون مجوز ویرایش، فرم گزینش در دسترس نیست
        $this->get(CooperationRequestResource::getUrl('edit', ['record' => $cooperation]))->assertForbidden();
    }

    public function test_recruitment_form_saves_sections_and_writes_history(): void
    {
        $cooperation = $this->cooperation();
        $admin = $this->admin(['view-cooperation-requests', 'edit-cooperation-requests']);
        $this->actingAs($admin, 'admin');

        Livewire::test(EditCooperationRequest::class, ['record' => $cooperation->getRouteKey()])
            ->fillForm([
                'status' => 'invited',
                'status_note' => 'برای شنبه دعوت شد',
                'recruitment.screening.reviewer' => 'خانم احمدی',
                'recruitment.screening.result' => 'initial_approval',
                'recruitment.contact.result' => 'ready',
                'recruitment.contact.interview_place' => 'office',
                'recruitment.decision.suggested_salary' => '25000000',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $cooperation->refresh();
        $this->assertSame('invited', $cooperation->status);
        $this->assertSame('خانم احمدی', $cooperation->recruitment['screening']['reviewer']);
        $this->assertSame('ready', $cooperation->recruitment['contact']['result']);

        $statusLog = $cooperation->logs()->where('action', 'status_changed')->sole();
        $this->assertSame(['new', 'invited', 'برای شنبه دعوت شد', $admin->id], [$statusLog->from_status, $statusLog->to_status, $statusLog->note, $statusLog->admin_id]);
        $this->assertStringContainsString('بررسی رزومه', $cooperation->logs()->where('action', 'recruitment_updated')->value('result'));
    }

    public function test_hiring_creates_employment_file_and_confidential_section_is_restricted(): void
    {
        $cooperation = $this->cooperation(['status' => 'accepted', 'recruitment' => [
            'decision' => ['suggested_unit' => 'واحد فنی', 'suggested_salary' => '30000000'],
            'hiring' => ['employee_code' => 'T-1001'],
        ]]);
        $hr = $this->admin(['view-cooperation-requests', 'edit-cooperation-requests', 'view-employment-files', 'edit-employment-files', 'view-employment-confidential']);
        $this->actingAs($hr, 'admin');

        Livewire::test(ViewCooperationRequest::class, ['record' => $cooperation->getRouteKey()])
            ->callAction('create_employment_file')
            ->assertHasNoActionErrors();

        $file = EmploymentFile::sole();
        $this->assertSame('T-1001', $file->personnel_code);
        $this->assertSame('technician', $file->person_type);
        $this->assertSame('واحد فنی', $file->general['employment']['unit']);
        $this->assertSame('30000000', $file->confidential['financial']['base_salary']);
        $this->assertSame('hired', $cooperation->fresh()->status);
        // اطلاعات محرمانه در دیتابیس رمزنگاری‌شده است
        $this->assertStringNotContainsString('30000000', DB::table('employment_files')->value('confidential'));

        // منابع انسانی: بخش محرمانه را می‌بیند و ویرایش می‌کند
        Livewire::test(EditEmploymentFile::class, ['record' => $file->getRouteKey()])
            ->assertFormSet(['confidential.financial.base_salary' => '30000000'])
            ->fillForm(['confidential.financial.sheba' => 'IR123456789012345678901234', 'general.employment.position' => 'تکنسین ارشد'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('IR123456789012345678901234', $file->fresh()->confidential['financial']['sheba']);
        $this->assertContains('اطلاعات مالی و حقوقی (محرمانه)', $file->logs()->latest('id')->value('changed_fields'));

        // مدیر مستقیم بدون مجوز محرمانه: بخش مالی را نمی‌بیند و ذخیره‌ی او آن را پاک نمی‌کند
        $manager = $this->admin(['view-employment-files', 'edit-employment-files'], 'manager@test.local');
        $this->actingAs($manager, 'admin');

        $this->get(\App\Filament\Resources\EmploymentFileResource::getUrl('edit', ['record' => $file]))
            ->assertOk()
            ->assertDontSee('IR123456789012345678901234')
            ->assertDontSee('مالی و حقوقی (محرمانه)');

        Livewire::test(EditEmploymentFile::class, ['record' => $file->getRouteKey()])
            ->assertFormFieldIsHidden('confidential.financial.sheba')
            ->fillForm(['general.employment.shift' => 'صبح'])
            ->call('save')
            ->assertHasNoFormErrors();

        $file->refresh();
        $this->assertSame('صبح', $file->general['employment']['shift']);
        $this->assertSame('IR123456789012345678901234', $file->confidential['financial']['sheba']);
    }

    public function test_applicant_files_are_downloadable_only_by_permitted_admins(): void
    {
        Storage::fake('local');
        $cooperation = $this->cooperation();
        $path = UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')->store("cooperation-requests/{$cooperation->id}", 'local');
        $cooperation->update(['resume_path' => $path]);
        $url = route('admin.cooperation-requests.file', ['cooperationRequest' => $cooperation->id, 'type' => 'resume']);

        $this->get($url)->assertRedirect(); // مهمان → صفحه‌ی ورود

        $this->actingAs($this->admin([], 'nobody@test.local'), 'admin');
        $this->get($url)->assertForbidden();

        $this->actingAs($this->admin(['view-cooperation-requests']), 'admin');
        $this->get($url)->assertOk();
        $this->get(route('admin.cooperation-requests.file', ['cooperationRequest' => $cooperation->id, 'type' => 'portfolio']))->assertNotFound();
    }
}
