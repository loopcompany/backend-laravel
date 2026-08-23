<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDebtRequestRequest extends FormRequest
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
            'type' => ['required', 'in:sponsor,free'],
            'description' => ['required', 'string', 'max:5000'],
            
            // برای نوع free (وام بدون بهره)
            'amount' => ['nullable', 'string', 'max:255', 'required_if:type,free'],
            'sponsor' => ['nullable', 'string', 'max:255', 'required_if:type,free'],
            'month' => ['nullable', 'integer', 'min:1', 'max:60', 'required_if:type,free'],
            'urgent_description' => ['nullable', 'string', 'max:2000'],
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
            'type.required' => 'نوع درخواست وام الزامی است.',
            'type.in' => 'نوع درخواست وام باید ضامن/ضمانت‌نامه یا وام بدون بهره باشد.',
            
            'description.required' => 'توضیحات درخواست الزامی است.',
            'description.string' => 'توضیحات باید متن باشد.',
            'description.max' => 'توضیحات نباید بیشتر از 5000 کاراکتر باشد.',
            
            'amount.string' => 'مبلغ وام باید متن باشد.',
            'amount.max' => 'مبلغ وام نباید بیشتر از 255 کاراکتر باشد.',
            'amount.required_if' => 'مبلغ وام برای درخواست وام بدون بهره الزامی است.',
            
            'sponsor.string' => 'وضعیت ضامن باید متن باشد.',
            'sponsor.max' => 'وضعیت ضامن نباید بیشتر از 255 کاراکتر باشد.',
            'sponsor.required_if' => 'وضعیت ضامن برای درخواست وام بدون بهره الزامی است.',
            
            'month.integer' => 'مدت زمان پرداخت باید عدد باشد.',
            'month.min' => 'مدت زمان پرداخت حداقل 1 ماه است.',
            'month.max' => 'مدت زمان پرداخت حداکثر 60 ماه است.',
            'month.required_if' => 'مدت زمان پرداخت برای درخواست وام بدون بهره الزامی است.',
            
            'urgent_description.string' => 'توضیحات اضطراری باید متن باشد.',
            'urgent_description.max' => 'توضیحات اضطراری نباید بیشتر از 2000 کاراکتر باشد.',
        ];
    }
}
