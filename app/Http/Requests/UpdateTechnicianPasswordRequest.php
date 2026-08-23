<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateTechnicianPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'رمز عبور فعلی الزامی است.',
            'current_password.string' => 'رمز عبور فعلی باید متن باشد.',
            'new_password.required' => 'رمز عبور جدید الزامی است.',
            'new_password.string' => 'رمز عبور جدید باید متن باشد.',
            'new_password.confirmed' => 'تکرار رمز عبور جدید مطابقت ندارد.',
            'new_password.min' => 'رمز عبور جدید باید حداقل 8 کاراکتر باشد.',
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'رمز عبور فعلی',
            'new_password' => 'رمز عبور جدید',
            'new_password_confirmation' => 'تکرار رمز عبور جدید',
        ];
    }
}
