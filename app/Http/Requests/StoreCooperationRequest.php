<?php

namespace App\Http\Requests;

use App\Helpers\Helper;
use App\Support\Careers\CareerOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * فرم «درخواست همکاری با لوپ» در سایت.
 */
class StoreCooperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $digits = fn ($v) => is_string($v) ? strtr(trim($v), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]) : $v;

        $this->merge([
            'mobile' => $digits($this->input('mobile')),
            'national_code' => $digits($this->input('national_code')),
            'age' => $digits($this->input('age')),
            'salary_amount' => is_string($this->input('salary_amount'))
                ? preg_replace('/[^0-9]/', '', $digits($this->input('salary_amount')))
                : $this->input('salary_amount'),
        ]);
    }

    public function rules(): array
    {
        $keys = fn (array $options) => Rule::in(array_keys($options));
        $isField = $this->input('job_title') === 'field_technician';

        return [
            // تله‌ی ربات: کاربر واقعی این فیلد مخفی را خالی می‌گذارد
            'website' => ['prohibited'],

            // ۱. اطلاعات فردی
            'full_name' => ['required', 'string', 'min:3', 'max:191'],
            'mobile' => ['required', 'regex:/^09[0-9]{9}$/'],
            'national_code' => ['required', 'regex:/^[0-9]{10}$/', function ($attribute, $value, $fail) {
                if (!Helper::is_valid_national_code($value)) {
                    $fail('کد ملی وارد شده معتبر نیست.');
                }
            }],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'age' => ['required', 'integer', 'min:16', 'max:70'],
            'gender' => ['required', $keys(CareerOptions::GENDERS)],
            'marital_status' => ['required', $keys(CareerOptions::MARITAL)],
            // نظام وظیفه برای آقایان اجباری است
            'military_status' => ['nullable', 'required_if:gender,male', $keys(CareerOptions::MILITARY)],
            'military_status_other' => ['nullable', 'required_if:military_status,other', 'string', 'max:191'],

            // ۲. موقعیت شغلی
            'job_title' => ['required', $keys(CareerOptions::JOB_TITLES)],
            'job_title_other' => ['nullable', 'required_if:job_title,other', 'string', 'max:191'],
            'cooperation_type' => ['required', $keys(CareerOptions::COOPERATION_TYPES)],

            // ۳. تحصیلات و سابقه
            'education_level' => ['required', $keys(CareerOptions::EDUCATION)],
            'education_level_other' => ['nullable', 'required_if:education_level,other', 'string', 'max:191'],
            'field_of_study' => ['nullable', 'string', 'max:191'],
            'work_experience' => ['required', $keys(CareerOptions::WORK_EXPERIENCE)],
            'related_experience' => ['required', $keys(CareerOptions::RELATED_EXPERIENCE)],

            // ۴. مهارت‌ها
            'skills' => ['nullable', 'array'],
            'skills.*' => ['nullable', $keys(CareerOptions::SKILL_LEVELS)],

            // ۵. تخصص‌های تکمیلی
            'interest_areas' => ['nullable', 'array'],
            'interest_areas.*' => [$keys(CareerOptions::INTEREST_AREAS)],
            'interest_other' => ['nullable', 'string', 'max:191'],
            'has_certificates' => ['required', $keys(CareerOptions::YES_NO)],
            'extra_skills' => ['nullable', 'string', 'max:2000'],

            // ۶. فقط تکنسین میدانی
            'field_info' => [$isField ? 'required' : 'nullable', 'array'],
            'field_info.has_vehicle' => [$isField ? 'required' : 'nullable', $keys(CareerOptions::YES_NO)],
            'field_info.vehicle_type' => [$isField ? 'required' : 'nullable', $keys(CareerOptions::VEHICLE_TYPES)],
            'field_info.has_license' => [$isField ? 'required' : 'nullable', $keys(CareerOptions::YES_NO)],
            'field_info.license_type' => ['nullable', $isField ? 'required_if:field_info.has_license,yes' : 'nullable', $keys(CareerOptions::LICENSE_TYPES)],
            'field_info.mission_range' => [$isField ? 'required' : 'nullable', $keys(CareerOptions::MISSION_RANGE)],
            'field_info.carry_equipment' => [$isField ? 'required' : 'nullable', $keys(CareerOptions::CARRY_EQUIPMENT)],
            'field_info.onsite_experience' => [$isField ? 'required' : 'nullable', $keys(CareerOptions::YES_NO)],

            // ۷. شرایط همکاری
            'start_availability' => ['required', $keys(CareerOptions::START_AVAILABILITY)],
            'salary_type' => ['required', $keys(CareerOptions::SALARY_TYPES)],
            'salary_amount' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
            'overtime' => ['required', $keys(CareerOptions::YES_NO_COORDINATION)],
            'shift_work' => ['required', $keys(CareerOptions::YES_NO_COORDINATION)],

            // ۸. رزومه و مدارک
            'resume' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'certificates' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,zip', 'max:10240'],
            'portfolio' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,zip', 'max:10240'],
            'portfolio_link' => ['nullable', 'url', 'max:255'],

            // ۹. تأیید
            'confirm_accuracy' => ['accepted'],
            'confirm_privacy' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'تکمیل «:attribute» الزامی است.',
            'required_if' => 'تکمیل «:attribute» الزامی است.',
            'in' => 'مقدار «:attribute» نامعتبر است.',
            'accepted' => ':attribute',
            'website.prohibited' => 'ارسال فرم نامعتبر است.',
            'mobile.regex' => 'شماره موبایل باید با 09 شروع شده و ۱۱ رقم باشد.',
            'national_code.regex' => 'کد ملی باید ۱۰ رقم باشد.',
            'age.min' => 'سن باید حداقل ۱۶ سال باشد.',
            'age.max' => 'سن واردشده معتبر نیست.',
            'military_status.required_if' => 'وضعیت نظام وظیفه برای آقایان الزامی است.',
            'mimes' => 'فرمت فایل «:attribute» مجاز نیست.',
            'max.file' => 'حجم فایل «:attribute» بیش از حد مجاز است.',
            'url' => 'لینک «:attribute» معتبر نیست.',
            'confirm_accuracy.accepted' => 'لطفاً صحت اطلاعات ثبت‌شده را تأیید کنید.',
            'confirm_privacy.accepted' => 'لطفاً موافقت خود با ثبت و نگهداری اطلاعات را اعلام کنید.',
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'نام و نام خانوادگی',
            'mobile' => 'شماره موبایل',
            'national_code' => 'کد ملی',
            'city' => 'شهر محل سکونت',
            'district' => 'منطقه محل سکونت',
            'age' => 'سن',
            'gender' => 'جنسیت',
            'marital_status' => 'وضعیت تأهل',
            'military_status' => 'وضعیت نظام وظیفه',
            'military_status_other' => 'توضیح وضعیت نظام وظیفه',
            'job_title' => 'عنوان شغلی',
            'job_title_other' => 'عنوان شغلی (سایر)',
            'cooperation_type' => 'نوع همکاری',
            'education_level' => 'آخرین مدرک تحصیلی',
            'education_level_other' => 'مدرک تحصیلی (سایر)',
            'field_of_study' => 'رشته تحصیلی',
            'work_experience' => 'سابقه کاری',
            'related_experience' => 'سابقه کار مرتبط',
            'has_certificates' => 'مدارک فنی و گواهینامه‌ها',
            'field_info' => 'اطلاعات ویژه تکنسین میدانی',
            'field_info.has_vehicle' => 'وسیله نقلیه شخصی',
            'field_info.vehicle_type' => 'نوع وسیله نقلیه',
            'field_info.has_license' => 'گواهینامه رانندگی',
            'field_info.license_type' => 'نوع گواهینامه',
            'field_info.mission_range' => 'امکان مراجعه به شرکت‌ها و سازمان‌ها',
            'field_info.carry_equipment' => 'امکان حمل تجهیزات و قطعات',
            'field_info.onsite_experience' => 'سابقه ارائه خدمات در محل مشتری',
            'start_availability' => 'زمان آمادگی برای شروع',
            'salary_type' => 'حقوق درخواستی',
            'salary_amount' => 'مبلغ حقوق درخواستی',
            'overtime' => 'امکان کار اضافه‌کاری',
            'shift_work' => 'امکان کار در شیفت',
            'resume' => 'رزومه',
            'certificates' => 'مدارک فنی',
            'portfolio' => 'نمونه‌کار',
            'portfolio_link' => 'لینک رزومه یا نمونه‌کار',
        ];
    }
}
