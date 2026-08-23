<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequestRequest extends FormRequest
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
            'type' => ['required', 'in:hourly,daily'],
            'date' => ['required', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:date', 'required_if:type,daily'],
            'houre' => ['nullable', 'date_format:H:i', 'required_if:type,hourly'],
            'description' => ['required', 'string', 'max:1000'],
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
            'type.required' => 'نوع مرخصی الزامی است.',
            'type.in' => 'نوع مرخصی باید ساعتی یا روزانه باشد.',
            
            'date.required' => 'تاریخ مرخصی الزامی است.',
            'date.date' => 'تاریخ مرخصی نامعتبر است.',
            
            
            'to_date.date' => 'تاریخ پایان نامعتبر است.',
           
            'to_date.required_if' => 'تاریخ پایان برای مرخصی روزانه الزامی است.',
            
            'houre.date_format' => 'فرمت ساعت باید HH:MM باشد.',
            'houre.required_if' => 'ساعت برای مرخصی ساعتی الزامی است.',
            
            'description.required' => 'توضیحات درخواست الزامی است.',
            'description.string' => 'توضیحات باید متن باشد.',
            'description.max' => 'توضیحات نباید بیشتر از 1000 کاراکتر باشد.',
        ];
    }
}
