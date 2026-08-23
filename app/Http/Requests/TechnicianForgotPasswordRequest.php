<?php

namespace App\Http\Requests;

use App\Helpers\Helper;
use Illuminate\Foundation\Http\FormRequest;

class TechnicianForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'referral_code' => 'required|string|max:50',
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
            'melicode' => [
                'required',
                'string',
                'size:10',
                function ($attribute, $value, $fail) {
                    if (!Helper::is_valid_national_code($value)) {
                        $fail('کد ملی وارد شده معتبر نیست.');
                    }
                },
            ],
            'email' => 'required|string|email|max:255',
            'hashApp' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'referral_code.required' => 'کد پرسنلی الزامی است.',
            'referral_code.max' => 'کد پرسنلی نباید بیش از 50 کاراکتر باشد.',
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.regex' => 'فرمت شماره موبایل صحیح نیست.',
            'melicode.required' => 'کد ملی الزامی است.',
            'melicode.size' => 'کد ملی باید ۱۰ رقم باشد.',
            'email.required' => 'آدرس ایمیل الزامی است.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
        ];
    }
}
