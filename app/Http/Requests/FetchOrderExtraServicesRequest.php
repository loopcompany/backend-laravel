<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FetchOrderExtraServicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => __("Order ID is required."),
            'order_id.integer' => __("The order ID must be an integer."),
            'order_id.exists' => __("The desired order was not found."),
        ];
    }
}
