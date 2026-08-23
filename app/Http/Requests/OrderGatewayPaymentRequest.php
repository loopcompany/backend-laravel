<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderGatewayPaymentRequest extends FormRequest
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
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'linking_url' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'شناسه سفارش الزامی است.',
            'order_id.integer' => 'شناسه سفارش باید عدد باشد.',
            'order_id.exists' => 'سفارش یافت نشد.',
            'linking_url.string' => 'آدرس لینک معتبر نیست.',
            'linking_url.max' => 'آدرس لینک نباید بیشتر از ۵۰۰ کاراکتر باشد.',
        ];
    }
}
