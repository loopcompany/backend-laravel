<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartOrderRequest extends FormRequest
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
            'orderId' => 'required|integer|exists:orders,id',
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
            'orderId.required' => 'شناسه سفارش الزامی است.',
            'orderId.integer' => 'شناسه سفارش باید عدد صحیح باشد.',
            'orderId.exists' => 'سفارش یافت نشد.',
        ];
    }
}
