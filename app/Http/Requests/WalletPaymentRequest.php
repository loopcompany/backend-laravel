<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletPaymentRequest extends FormRequest
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
            'orderId' => ['required', 'integer', 'exists:orders,id'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'orderId.required' => 'شناسه سفارش الزامی است.',
            'orderId.integer' => 'شناسه سفارش باید عدد باشد.',
            'orderId.exists' => 'سفارش یافت نشد.',
        ];
    }
}

