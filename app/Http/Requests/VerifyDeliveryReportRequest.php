<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyDeliveryReportRequest extends FormRequest
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
            'code' => 'required|string|size:6|regex:/^[0-9]{6}$/',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'کد تایید الزامی است.',
            'code.string' => 'کد تایید باید رشته متنی باشد.',
            'code.size' => 'کد تایید باید 6 رقم باشد.',
            'code.regex' => 'کد تایید فقط باید شامل اعداد باشد.',
        ];
    }
}
