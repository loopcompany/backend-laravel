<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryReportRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'melicode' => 'sometimes|string|size:10',
            'product_info' => 'sometimes|string',
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
            'name.string' => 'نام باید رشته متنی باشد.',
            'name.max' => 'نام نباید بیشتر از 255 کاراکتر باشد.',
            'melicode.string' => 'کد ملی باید رشته متنی باشد.',
            'melicode.size' => 'کد ملی باید 10 رقم باشد.',
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
