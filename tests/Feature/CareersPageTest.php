<?php

namespace Tests\Feature;

use App\Models\CooperationRequest;
use App\Services\ShahkarService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

/**
 * صفحه‌ی «همکاری با لوپ»: فرم درخواست، استعلام شاهکار، کد پیگیری، پیامک و استعلام وضعیت.
 */
class CareersPageTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_NATIONAL_CODE = '0499370899';

    /** @var \Mockery\MockInterface */
    private $shahkar;

    /** @var \Mockery\MockInterface */
    private $sms;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->shahkar = Mockery::mock(ShahkarService::class);
        $this->shahkar->shouldReceive('inquiry')->andReturn(['success' => true, 'matched' => true, 'message' => 'ok'])->byDefault();
        $this->app->instance(ShahkarService::class, $this->shahkar);

        $this->sms = Mockery::mock(SmsService::class);
        $this->sms->shouldReceive('sendCooperationRequestReceived')->andReturn(true)->byDefault();
        $this->app->instance(SmsService::class, $this->sms);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'زهرا محمدی',
            'mobile' => '09121234567',
            'national_code' => self::VALID_NATIONAL_CODE,
            'city' => 'تهران',
            'district' => 'منطقه ۵',
            'age' => '26',
            'gender' => 'female',
            'marital_status' => 'single',
            'job_title' => 'internal_technician',
            'cooperation_type' => 'full_time',
            'education_level' => 'bachelor',
            'field_of_study' => 'مهندسی کامپیوتر',
            'work_experience' => '1_3',
            'related_experience' => 'lt1',
            'skills' => ['hw_parts' => 'advanced', 'sw_windows_install' => 'intermediate', 'unknown_skill' => 'advanced'],
            'interest_areas' => ['laptop', 'network'],
            'has_certificates' => 'yes',
            'start_availability' => 'immediately',
            'salary_type' => 'negotiable',
            'overtime' => 'coordination',
            'shift_work' => 'no',
            'confirm_accuracy' => '1',
            'confirm_privacy' => '1',
        ], $overrides);
    }

    public function test_page_renders_with_texts_form_and_tracking(): void
    {
        $this->get('/careers')
            ->assertOk()
            ->assertSee('آینده را با هم می‌سازیم')
            ->assertSee('فرصت‌های تازه در دنیای فناوری')
            ->assertSee('یک گام برای شروع مسیری تازه')
            ->assertSee('فرم درخواست همکاری با لوپ')
            ->assertSee('لحیم‌کاری و تعمیرات برد')
            ->assertSee('اطلاعات ویژه تکنسین‌های میدانی')
            ->assertSee('استعلام کد پیگیری همکاری')
            ->assertSee('assets/new-style/careers/hero-desktop.jpg', false);
    }

    public function test_submit_creates_request_with_tracking_code_sms_and_files(): void
    {
        $this->sms->shouldReceive('sendCooperationRequestReceived')
            ->once()
            ->withArgs(fn ($phone, $code) => $phone === '09121234567' && preg_match('/^PCS-\d{6}$/', $code) === 1)
            ->andReturn(true);

        $response = $this->post('/careers', $this->payload([
            'mobile' => '۰۹۱۲۱۲۳۴۵۶۷', // ارقام فارسی
            'resume' => UploadedFile::fake()->create('cv.pdf', 300, 'application/pdf'),
        ]));

        $response->assertRedirect(route('web.careers') . '#apply');
        $cooperation = CooperationRequest::sole();
        $this->assertMatchesRegularExpression('/^PCS-\d{6}$/', $cooperation->tracking_code);
        $this->assertSame($cooperation->tracking_code, session('cooperation_submitted')['tracking_code']);
        $this->assertSame('new', $cooperation->status);
        $this->assertSame('09121234567', $cooperation->mobile);
        $this->assertSame('verified', $cooperation->shahkar_status);
        $this->assertTrue($cooperation->sms_sent);
        $this->assertNull($cooperation->military_status);
        $this->assertNull($cooperation->field_info);
        $this->assertSame('advanced', $cooperation->skills['hw_parts']);
        $this->assertSame('none', $cooperation->skills['hw_soldering']);
        $this->assertArrayNotHasKey('unknown_skill', $cooperation->skills);
        $this->assertCount(36, $cooperation->skills);
        Storage::disk('local')->assertExists($cooperation->resume_path);
        $this->assertSame('submitted', $cooperation->logs()->value('action'));

        $this->get('/careers')->assertSee($cooperation->tracking_code);
    }

    public function test_military_status_is_required_for_men_only(): void
    {
        $this->post('/careers', $this->payload(['gender' => 'male']))
            ->assertSessionHasErrors('military_status');

        $this->post('/careers', $this->payload(['gender' => 'male', 'military_status' => 'completed']))
            ->assertSessionHasNoErrors();
        $this->assertSame('completed', CooperationRequest::sole()->military_status);
    }

    public function test_field_technician_section_is_required_only_for_field_technicians(): void
    {
        $this->post('/careers', $this->payload(['job_title' => 'field_technician']))
            ->assertSessionHasErrors(['field_info', 'field_info.has_vehicle', 'field_info.mission_range']);

        $this->post('/careers', $this->payload([
            'job_title' => 'field_technician',
            'field_info' => [
                'has_vehicle' => 'yes', 'vehicle_type' => 'motorcycle', 'has_license' => 'yes',
                'mission_range' => 'city', 'carry_equipment' => 'limited', 'onsite_experience' => 'no',
            ],
        ]))->assertSessionHasErrors('field_info.license_type');

        $this->post('/careers', $this->payload([
            'job_title' => 'field_technician',
            'field_info' => [
                'has_vehicle' => 'yes', 'vehicle_type' => 'motorcycle', 'has_license' => 'yes', 'license_type' => 'motorcycle',
                'mission_range' => 'city', 'carry_equipment' => 'limited', 'onsite_experience' => 'no',
            ],
        ]))->assertSessionHasNoErrors();

        $this->assertSame('motorcycle', CooperationRequest::sole()->field_info['license_type']);
    }

    public function test_validation_rejects_invalid_national_code_missing_consents_and_bots(): void
    {
        $this->post('/careers', $this->payload(['national_code' => '1234567890']))->assertSessionHasErrors('national_code');
        $this->post('/careers', $this->payload(['confirm_privacy' => null]))->assertSessionHasErrors('confirm_privacy');
        $this->post('/careers', $this->payload(['job_title' => 'other']))->assertSessionHasErrors('job_title_other');
        $this->post('/careers', $this->payload(['website' => 'http://spam']))->assertSessionHasErrors('website');
        $this->post('/careers', $this->payload(['resume' => UploadedFile::fake()->create('x.exe', 10)]))->assertSessionHasErrors('resume');

        $this->assertSame(0, CooperationRequest::count());
    }

    public function test_shahkar_mismatch_is_rejected_but_unavailable_service_is_accepted_as_unverified(): void
    {
        $this->shahkar->shouldReceive('inquiry')->once()->andReturn(['success' => true, 'matched' => false, 'message' => '']);
        $this->post('/careers', $this->payload())->assertSessionHasErrors('form');
        $this->assertSame(0, CooperationRequest::count());

        $this->shahkar->shouldReceive('inquiry')->once()->andReturn(['success' => false, 'matched' => false, 'message' => 'down']);
        $this->post('/careers', $this->payload())->assertSessionHasNoErrors();
        $this->assertSame('unverified', CooperationRequest::sole()->shahkar_status);
    }

    public function test_one_open_request_per_national_code(): void
    {
        $this->post('/careers', $this->payload())->assertSessionHasNoErrors();
        $this->post('/careers', $this->payload())->assertSessionHasErrors('form');

        CooperationRequest::sole()->update(['status' => 'rejected']);
        $this->post('/careers', $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(2, CooperationRequest::count());
    }

    public function test_tracking_requires_matching_code_and_mobile(): void
    {
        $this->post('/careers', $this->payload());
        $cooperation = CooperationRequest::sole();
        $cooperation->update(['status' => 'invited']);

        $this->post('/careers/track', ['tracking_code' => $cooperation->tracking_code, 'track_mobile' => '09120000000'])
            ->assertSessionHasErrors('tracking_code', null, 'track');

        $this->post('/careers/track', ['tracking_code' => strtolower($cooperation->tracking_code), 'track_mobile' => '09121234567'])
            ->assertSessionHasNoErrors();
        $this->assertSame('برای مصاحبه دعوت شده‌اید؛ زمان مصاحبه با شما هماهنگ می‌شود.', session('cooperation_tracked')['status_message']);

        $this->get('/careers')->assertSee('برای مصاحبه دعوت شده‌اید');
    }
}
