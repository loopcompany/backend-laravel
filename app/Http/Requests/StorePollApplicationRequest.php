<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePollApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'app_rate' => ['required', 'string', 'in:خوب,متوسط,ضعیف'],
            'tech_rate' => ['required', 'string', 'in:خوب,متوسط,ضعیف'],
            'support_rate' => ['required', 'string', 'in:خوب,متوسط,ضعیف'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'app_rate.required' => 'امتیاز اپلیکیشن الزامی است.',
            'app_rate.in' => 'امتیاز اپلیکیشن باید یکی از مقادیر خوب، متوسط یا ضعیف باشد.',
            'tech_rate.required' => 'امتیاز تکنسین الزامی است.',
            'tech_rate.in' => 'امتیاز تکنسین باید یکی از مقادیر خوب، متوسط یا ضعیف باشد.',
            'support_rate.required' => 'امتیاز پشتیبانی الزامی است.',
            'support_rate.in' => 'امتیاز پشتیبانی باید یکی از مقادیر خوب، متوسط یا ضعیف باشد.',
            'description.max' => 'توضیحات نباید بیشتر از 1000 کاراکتر باشد.',
        ];
    }
}
