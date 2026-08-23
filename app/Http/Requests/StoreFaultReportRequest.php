<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaultReportRequest extends FormRequest
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
            'order_id' => 'required|integer|exists:orders,id',
            'product_name' => 'nullable|string|max:255',
            'ordered_at' => 'nullable|date',
            'delivered_at' => 'nullable|date|after_or_equal:ordered_at',
            'technician_code' => 'nullable|string|max:255',
            'paid_price' => 'nullable|string|max:255',
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
            'order_id.required' => 'شناسه سفارش الزامی است.',
            'order_id.integer' => 'شناسه سفارش باید عدد باشد.',
            'order_id.exists' => 'سفارش یافت نشد.',
            'product_name.string' => 'نام محصول باید متن باشد.',
            'product_name.max' => 'نام محصول حداکثر 255 کاراکتر مجاز است.',
            'ordered_at.date' => 'تاریخ سفارش معتبر نیست.',
            'delivered_at.date' => 'تاریخ تحویل معتبر نیست.',
            'delivered_at.after_or_equal' => 'تاریخ تحویل باید بعد یا برابر با تاریخ سفارش باشد.',
            'technician_code.string' => 'کد تکنسین باید متن باشد.',
            'technician_code.max' => 'کد تکنسین حداکثر 255 کاراکتر مجاز است.',
            'paid_price.string' => 'مبلغ پرداختی باید متن باشد.',
            'paid_price.max' => 'مبلغ پرداختی حداکثر 255 کاراکتر مجاز است.',
            'description.string' => 'توضیحات باید متن باشد.',
            'description.max' => 'توضیحات حداکثر 1000 کاراکتر مجاز است.',
        ];
    }
}
