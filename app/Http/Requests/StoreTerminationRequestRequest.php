<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTerminationRequestRequest extends FormRequest
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
            'type' => 'required|in:temporary,permanent',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'description' => 'required|string|max:5000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'نوع درخواست الزامی است.',
            'type.in' => 'نوع درخواست باید یکی از مقادیر مجاز باشد.',
            
            'start_date.required' => 'تاریخ شروع الزامی است.',
            'start_date.date' => 'تاریخ شروع نامعتبر است.',
            
            
            'end_date.date' => 'تاریخ پایان نامعتبر است.',
            
            'end_date.required_if' => 'تاریخ پایان برای قطع همکاری موقت الزامی است.',
            
            'description.required' => 'توضیحات الزامی است.',
            'description.string' => 'توضیحات باید متن باشد.',
            'description.max' => 'توضیحات نباید بیشتر از 5000 کاراکتر باشد.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'نوع درخواست',
            'start_date' => 'تاریخ شروع',
            'end_date' => 'تاریخ پایان',
            'description' => 'توضیحات',
        ];
    }
}
