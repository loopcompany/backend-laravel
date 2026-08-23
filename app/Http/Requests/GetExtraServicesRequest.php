<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetExtraServicesRequest extends FormRequest
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
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category_id.required' => __("Category ID is required."),
            'category_id.integer' => __("The category ID must be a number."),
            'category_id.exists' => __("The desired category was not found."),
            
            'order_id.required' => __("Order ID is required."),
            'order_id.integer' => __("The order ID must be a number."),
            'order_id.exists' => __("The desired order was not found."),
        ];
    }
}
