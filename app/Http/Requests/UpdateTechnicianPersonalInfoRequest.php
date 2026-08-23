<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTechnicianPersonalInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $technicianId = $this->user('sanctum')->id;

        return [
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'melicode' => ['nullable', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],
            'birth_date' => ['nullable', 'date'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'issued_from' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'string', 'in:مجرد,متأهل'],
            'education_status' => ['nullable', 'string', 'max:100'],
            'education_field' => ['nullable', 'string', 'max:100'],
            'telephone' => ['nullable', 'string', 'regex:/^0[0-9]{2,3}[0-9]{8}$/'],
            'email' => [
                'nullable',
                'email',
                Rule::unique('technicians', 'email')->ignore($technicianId)
            ],
            'certificate_number' => ['nullable', 'string', 'max:50'],
            'licence_date' => ['nullable', 'string', 'max:20'],
            'certificate_issue_date' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'home_address' => ['nullable', 'string', 'max:500'],
            'home_postal_code' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'technician_type' => ['nullable', 'string', 'max:100'],
            'other_referral_code' => [
                'nullable',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($technicianId) {
                    // فقط اگر تکنسین قبلا کد معرف ثبت نکرده باشد
                    $technician = \App\Models\Technician::find($technicianId);
                    if ($technician && $technician->other_referral_code && $value) {
                        $fail('کد معرف قبلاً ثبت شده و قابل تغییر نیست.');
                    }
                }
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'profile_photo.image' => 'فایل باید یک تصویر باشد.',
            'profile_photo.mimes' => 'فرمت تصویر باید jpeg، png یا jpg باشد.',
            'profile_photo.max' => 'حجم تصویر نباید بیشتر از 2 مگابایت باشد.',
            'melicode.size' => 'کد ملی باید 10 رقم باشد.',
            'melicode.regex' => 'کد ملی باید فقط شامل اعداد باشد.',
            'birth_date.date' => 'تاریخ تولد نامعتبر است.',
            'father_name.max' => 'نام پدر نباید بیشتر از 100 کاراکتر باشد.',
            'issued_from.max' => 'محل صدور نباید بیشتر از 100 کاراکتر باشد.',
            'serial_number.max' => 'شماره شناسنامه نباید بیشتر از 50 کاراکتر باشد.',
            'marital_status.in' => 'وضعیت تاهل باید مجرد یا متأهل باشد.',
            'education_status.max' => 'وضعیت تحصیلات نباید بیشتر از 100 کاراکتر باشد.',
            'education_field.max' => 'رشته تحصیلی نباید بیشتر از 100 کاراکتر باشد.',
            'telephone.regex' => 'فرمت شماره تلفن ثابت صحیح نیست.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'city.max' => 'شهر نباید بیشتر از 100 کاراکتر باشد.',
            'region.max' => 'منطقه نباید بیشتر از 100 کاراکتر باشد.',
            'home_postal_code.regex' => 'کد پستی باید 10 رقم باشد.',
        ];
    }
}
