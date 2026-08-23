<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPhoneRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
                'exists:users,phone',
            ],
            'verification_code' => [
                'required',
                'string',
                'size:6',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.regex' => 'فرمت شماره موبایل صحیح نیست.',
            'phone.exists' => 'شماره موبایل در سیستم یافت نشد.',
            'verification_code.required' => 'کد تایید الزامی است.',
            'verification_code.size' => 'کد تایید باید ۶ رقم باشد.',
        ];
    }
}