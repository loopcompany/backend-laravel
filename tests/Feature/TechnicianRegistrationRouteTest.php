<?php

namespace Tests\Feature;

use App\Models\Expertise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TechnicianRegistrationRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_path_shows_form_instead_of_public_technician_profile(): void
    {
        $this->get('/technician/register')
            ->assertOk()
            ->assertSee('ثبت‌نام تکنسین')
            ->assertDontSee('تکنسین یافت نشد');
    }

    public function test_technician_registration_records_site_and_native_origins(): void
    {
        config(['smsir.enabled' => false]);
        Storage::fake('public');
        $expertise = Expertise::create(['title' => 'آزمون']);
        $form = [
            'name' => 'تکنسین آزمون', 'father_name' => 'علی', 'issued_from' => 'تهران', 'serial_number' => '12345',
            'marital_status' => 'مجرد', 'military_status' => 'اتمام خدمت', 'education_status' => 'کارشناسی',
            'education_field' => 'رایانه', 'telephone' => '02100000000', 'home_postal_code' => '1234567890',
            'region' => '18', 'city' => 'تهران', 'home_address' => 'تهران خیابان نمونه ساختمان آزمایش پلاک ده',
            'idea' => 'آزمون', 'software_skill' => 'آزمون', 'hardware_skill' => 'آزمون',
            'software_weakness' => 'آزمون', 'hardware_weakness' => 'آزمون',
        ];

        $this->postJson('/api/technician/register', $form + ['phone' => '09120000021'], ['User-Agent' => 'okhttp/4.12'])
            ->assertCreated();
        $this->postJson('/api/technician/register', $form + ['phone' => '09120000022'], ['Sec-Fetch-Mode' => 'cors'])
            ->assertCreated();
        $this->post('/technician/register', $form + [
            'phone' => '09120000023', 'melicode' => '1234567890', 'birth_date' => '1370/01/01',
            'email' => 'technician@example.test', 'accept_terms' => '1', 'expertise_ids' => [$expertise->id],
            'resume' => UploadedFile::fake()->create('resume.pdf', 10, 'application/pdf'),
        ])->assertRedirect(route('web.technician.verify-phone-form'));

        $this->assertDatabaseHas('technicians', ['phone' => '09120000021', 'registration_method' => 1]);
        $this->assertDatabaseHas('technicians', ['phone' => '09120000022', 'registration_method' => 3]);
        $this->assertDatabaseHas('technicians', ['phone' => '09120000023', 'registration_method' => 3]);
    }
}
