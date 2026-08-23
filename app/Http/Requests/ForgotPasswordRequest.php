<?php

namespace App\Http\Requests;

use App\Helpers\Helper;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
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
            'melicode' => [
                'required',
                'string',
                'size:10',
                function ($attribute, $value, $fail) {
                    if (!Helper::is_valid_national_code($value)) {
                        $fail(__("The national code entered is not valid."));
                    }
                },
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
            ],
            'hashApp' => [
                'nullable',
                'string'
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
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
            'melicode.required' => __("National ID is required."),
            'melicode.size' => __("The national code must be 10 digits."),
            'phone.required' => __("Mobile number is required."),
            'phone.regex' => __("The mobile number format is not correct."),
            'email.required' => __("Email address is required."),
            'email.email' => __("The email format is not correct."),
        ];
    }
}
