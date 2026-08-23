<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'discount_code' => 'required|string|max:50',
            'category_id' => 'required|integer|exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'discount_code.required' => __('Discount code required.'),
            'discount_code.max' => __("The discount code should not exceed 50 characters."),
            
            'category_id.required' => __("Category is required."),
            'category_id.integer' => __("The category must be an integer."),
            'category_id.exists' => __("The selected category is not valid."),
        ];
    }
}
