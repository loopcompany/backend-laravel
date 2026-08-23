<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExtraServicesRequest extends FormRequest
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
            // extras can be an empty array to indicate removing all extra services
            'extras' => ['nullable', 'array'],
            'extras.*.id' => ['required_with:extras', 'integer', 'exists:extra_services,id'],
            // client sends extraDetailId (camelCase) in payload
            'extras.*.extraDetailId' => ['nullable', 'integer', 'exists:extra_service_details,id'],
            'extras.*.price' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'شناسه سفارش الزامی است.',
            'order_id.integer' => 'شناسه سفارش باید عدد باشد.',
            'order_id.exists' => 'سفارش مورد نظر یافت نشد.',
            
            'extras.array' => 'لیست خدمات اضافی باید آرایه باشد.',
            'extras.*.id.required_with' => 'شناسه خدمت اضافی الزامی است.',
            
            'extras.*.id.required' => 'شناسه خدمت اضافی الزامی است.',
            'extras.*.id.integer' => 'شناسه خدمت اضافی باید عدد باشد.',
            'extras.*.id.exists' => 'خدمت اضافی مورد نظر یافت نشد.',
            
            'extras.*.extraDetailId.integer' => 'شناسه جزئیات خدمت باید عدد باشد.',
            'extras.*.extraDetailId.exists' => 'جزئیات خدمت مورد نظر یافت نشد.',
            
            'extras.*.price.integer' => 'قیمت باید عدد باشد.',
            'extras.*.price.min' => 'قیمت نمی‌تواند منفی باشد.',
        ];
    }
}
