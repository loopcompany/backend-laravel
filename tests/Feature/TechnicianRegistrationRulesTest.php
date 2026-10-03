<?php

namespace Tests\Feature;

use App\Http\Requests\TechnicianRegistrationRequest;
use App\Models\Technician;
use App\Rules\TechnicianBirthYear;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

/**
 * شرط سال تولد غلتان و گزینه‌های وضعیت نظام وظیفه در ثبت‌نام تکنسین.
 */
class TechnicianRegistrationRulesTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function passes(?string $birthDate): bool
    {
        return Validator::make(['birth_date' => $birthDate], ['birth_date' => [new TechnicianBirthYear()]])->passes();
    }

    public function test_cutoff_moves_forward_on_farvardin_first(): void
    {
        // ۲۹ اسفند ۱۴۰۵ ← هنوز سال ۱۴۰۵: سقف ۱۳۸۷
        Carbon::setTestNow(Carbon::create(2027, 3, 20, 12, 0, 0, 'Asia/Tehran'));
        $this->assertSame(1387, TechnicianBirthYear::maxAllowedJalaliYear());
        $this->assertTrue($this->passes('1387/12/29'));
        $this->assertFalse($this->passes('1388/01/01'));

        // ۱ فروردین ۱۴۰۶ ← سقف ۱۳۸۸
        Carbon::setTestNow(Carbon::create(2027, 3, 21, 12, 0, 0, 'Asia/Tehran'));
        $this->assertSame(1388, TechnicianBirthYear::maxAllowedJalaliYear());
        $this->assertTrue($this->passes('1388/01/01'));
        $this->assertFalse($this->passes('1389/01/01'));
    }

    public function test_accepts_jalali_gregorian_and_persian_digit_formats(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 3, 12, 0, 0, 'Asia/Tehran')); // ۱۴۰۵

        $this->assertTrue($this->passes('1370-05-12'));
        $this->assertTrue($this->passes('۱۳۸۷/۰۶/۰۱'));
        $this->assertFalse($this->passes('۱۳۹۰/۰۱/۰۱'));
        // میلادی: 2008-03-19 = ۲۹ اسفند ۱۳۸۶ (مجاز)، 2009-03-21 = ۱ فروردین ۱۳۸۸ (غیرمجاز)
        $this->assertTrue($this->passes('2008-03-19'));
        $this->assertFalse($this->passes('2009-03-21'));
        // خالی مجاز است (فیلد اختیاری)، رشته‌ی نامعتبر نه
        $this->assertTrue($this->passes(null));
        $this->assertFalse($this->passes('abc'));
        $this->assertFalse($this->passes('1370/13/01'));
    }

    public function test_registration_request_rejects_too_young_with_persian_message(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 3, 12, 0, 0, 'Asia/Tehran'));

        $validator = Validator::make(
            ['birth_date' => '1390/01/01', 'military_status' => 'معافیت'],
            (new TechnicianRegistrationRequest())->rules()
        );

        $this->assertTrue($validator->errors()->has('birth_date'));
        $this->assertStringContainsString('1387', $validator->errors()->first('birth_date'));
        $this->assertFalse($validator->errors()->has('military_status'));
    }

    public function test_every_app_military_status_is_accepted_and_storable(): void
    {
        $appOptions = ['مشمول خدمت', 'درانتظار اعزام', 'فاقد سابقه خدمت', 'اتمام خدمت', 'معافیت', 'در حال تحصیل'];
        $rules = ['military_status' => (new TechnicianRegistrationRequest())->rules()['military_status']];

        foreach ($appOptions as $option) {
            $this->assertContains($option, Technician::MILITARY_STATUSES);
            $this->assertTrue(Validator::make(['military_status' => $option], $rules)->passes(), $option);
        }
        $this->assertFalse(Validator::make(['military_status' => 'نامعتبر'], $rules)->passes());

        // ستون دیگر ENUM سه‌مقداری نیست
        $id = $this->makeTechnician(['military_status' => 'درانتظار اعزام'])->id;
        $this->assertSame('درانتظار اعزام', DB::table('technicians')->where('id', $id)->value('military_status'));
    }
}
