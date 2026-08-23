<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryReportRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'melicode' => 'required|string|size:10',
            'product_info' => 'required|string',
            'accessories' => 'nullable|string|max:255',
            'label_code' => 'nullable|string|max:255',
            'appearance_defect' => 'nullable|string|max:255',
            'user_description' => 'nullable|string',
            'technical_description' => 'nullable|string',
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
            'order_id.required' => 'شناسه سفارش الزامی است.',
            'order_id.integer' => 'شناسه سفارش باید عدد صحیح باشد.',
            'order_id.exists' => 'سفارش یافت نشد.',
            'name.required' => 'نام الزامی است.',
            'name.string' => 'نام باید رشته متنی باشد.',
            'name.max' => 'نام نباید بیشتر از 255 کاراکتر باشد.',
            'melicode.required' => 'کد ملی الزامی است.',
            'melicode.string' => 'کد ملی باید رشته متنی باشد.',
            'melicode.size' => 'کد ملی باید 10 رقم باشد.',
            'product_info.required' => 'اطلاعات محصول الزامی است.',
            'product_info.string' => 'اطلاعات محصول باید رشته متنی باشد.',
            'accessories.string' => 'متعلقات باید رشته متنی باشد.',
            'accessories.max' => 'متعلقات نباید بیشتر از 255 کاراکتر باشد.',
            'label_code.string' => 'کد برچسب باید رشته متنی باشد.',
            'label_code.max' => 'کد برچسب نباید بیشتر از 255 کاراکتر باشد.',
            'appearance_defect.string' => 'عیوب ظاهری باید رشته متنی باشد.',
            'appearance_defect.max' => 'عیوب ظاهری نباید بیشتر از 255 کاراکتر باشد.',
            'user_description.string' => 'توضیحات کاربر باید رشته متنی باشد.',
            'technical_description.string' => 'توضیحات فنی باید رشته متنی باشد.',
        ];
    }
}
