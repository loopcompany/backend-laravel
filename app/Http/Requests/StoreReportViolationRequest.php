<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportViolationRequest extends FormRequest
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
            'subject' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'date.required' => 'تاریخ الزامی است.',
            'date.max' => 'تاریخ نباید بیشتر از 50 کاراکتر باشد.',
            'amount.required' => 'مبلغ الزامی است.',
            'amount.max' => 'مبلغ نباید بیشتر از 100 کاراکتر باشد.',
            'description.required' => 'توضیحات الزامی است.',
            'description.max' => 'توضیحات نباید بیشتر از 2000 کاراکتر باشد.',
            'subject.max' => 'موضوع نباید بیشتر از 255 کاراکتر باشد.',
            'name.max' => 'نام نباید بیشتر از 255 کاراکتر باشد.',
        ];
    }
}
