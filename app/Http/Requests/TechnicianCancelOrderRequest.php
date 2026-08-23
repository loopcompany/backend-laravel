<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TechnicianCancelOrderRequest extends FormRequest
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
            'technician_cancel_reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'technician_cancel_reason.required' => 'دلیل لغو سفارش الزامی است.',
            'technician_cancel_reason.string' => 'دلیل لغو باید یک متن باشد.',
            'technician_cancel_reason.max' => 'دلیل لغو نمی‌تواند بیشتر از 1000 کاراکتر باشد.',
        ];
    }
}
