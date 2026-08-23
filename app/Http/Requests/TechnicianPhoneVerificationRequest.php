<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TechnicianPhoneVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
            'code' => 'required|string|size:6|regex:/^[0-9]{6}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'شماره تلفن الزامی است.',
            'phone.regex' => 'شماره تلفن باید با 09 شروع شده و 11 رقم باشد.',
            
            'code.required' => 'کد تأیید الزامی است.',
            'code.size' => 'کد تأیید باید 6 رقم باشد.',
            'code.regex' => 'کد تأیید باید فقط شامل اعداد باشد.',
        ];
    }
}