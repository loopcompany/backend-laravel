<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TechnicianLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'referral_code' => 'required|string|max:50',
            'password' => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'referral_code.required' => 'کد پرسنلی الزامی است.',
            'referral_code.max' => 'کد پرسنلی نباید بیش از 50 کاراکتر باشد.',
            
            'password.required' => 'رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل 6 کاراکتر باشد.',
        ];
    }
}