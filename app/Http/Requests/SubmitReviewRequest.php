<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitReviewRequest extends FormRequest
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
            'technician_id' => ['required', 'integer', 'exists:technicians,id'],
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'application_rate' => ['required', 'integer', 'min:1', 'max:5'],
            'technician_rate' => ['required', 'integer', 'min:1', 'max:5'],
            'support_rate' => ['required', 'integer', 'min:1', 'max:5'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'technician_id.required' => 'شناسه تکنسین الزامی است.',
            'technician_id.integer' => 'شناسه تکنسین باید عدد باشد.',
            'technician_id.exists' => 'تکنسین یافت نشد.',
            
            'order_id.required' => 'شناسه سفارش الزامی است.',
            'order_id.integer' => 'شناسه سفارش باید عدد باشد.',
            'order_id.exists' => 'سفارش یافت نشد.',
            
            'application_rate.required' => 'امتیاز اپلیکیشن الزامی است.',
            'application_rate.integer' => 'امتیاز اپلیکیشن باید عدد باشد.',
            'application_rate.min' => 'امتیاز اپلیکیشن حداقل ۱ است.',
            'application_rate.max' => 'امتیاز اپلیکیشن حداکثر ۵ است.',
            
            'technician_rate.required' => 'امتیاز تکنسین الزامی است.',
            'technician_rate.integer' => 'امتیاز تکنسین باید عدد باشد.',
            'technician_rate.min' => 'امتیاز تکنسین حداقل ۱ است.',
            'technician_rate.max' => 'امتیاز تکنسین حداکثر ۵ است.',
            
            'support_rate.required' => 'امتیاز پشتیبانی الزامی است.',
            'support_rate.integer' => 'امتیاز پشتیبانی باید عدد باشد.',
            'support_rate.min' => 'امتیاز پشتیبانی حداقل ۱ است.',
            'support_rate.max' => 'امتیاز پشتیبانی حداکثر ۵ است.',
            
            'description.string' => 'توضیحات باید متن باشد.',
            'description.max' => 'توضیحات نباید بیشتر از ۱۰۰۰ کاراکتر باشد.',
        ];
    }
}
