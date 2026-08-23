<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEducationRegisterationRequest extends FormRequest
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
            'telephone' => 'required|string|regex:/^0[0-9]{10}$/|max:11',
            'phone' => 'required|string|regex:/^09[0-9]{9}$/|max:11',
            'address' => 'required|string|max:500',
            'description' => 'nullable|string|max:1000',
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
            'telephone.required' => 'شماره ثابت الزامی است.',
            'telephone.string' => 'شماره ثابت باید متن باشد.',
            'telephone.regex' => 'فرمت شماره ثابت صحیح نیست. (مثال: 02188888888)',
            'telephone.max' => 'شماره ثابت حداکثر 11 کاراکتر مجاز است.',
            
            'phone.required' => 'شماره همراه الزامی است.',
            'phone.string' => 'شماره همراه باید متن باشد.',
            'phone.regex' => 'فرمت شماره همراه صحیح نیست. (مثال: 09123456789)',
            'phone.max' => 'شماره همراه حداکثر 11 کاراکتر مجاز است.',
            
            'address.required' => 'آدرس الزامی است.',
            'address.string' => 'آدرس باید متن باشد.',
            'address.max' => 'آدرس حداکثر 500 کاراکتر مجاز است.',
            
            'description.string' => 'توضیحات باید متن باشد.',
            'description.max' => 'توضیحات حداکثر 1000 کاراکتر مجاز است.',
        ];
    }
}
