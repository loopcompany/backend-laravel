<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTechnicianOrderReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => [
                'required',
                'integer',
                'exists:orders,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'melicode' => [
                'required',
                'string',
                'digits:10',
                function ($attribute, $value, $fail) {
                    if (!\App\Helpers\Helper::is_valid_national_code($value)) {
                        $fail('کد ملی وارد شده معتبر نیست.');
                    }
                },
            ],
            'product_name' => 'nullable|string|max:255',
            'product_brand' => 'nullable|string|max:255',
            'product_model' => 'nullable|string|max:255',
            'product_color' => 'nullable|string|max:100',
            'product_serial_number' => 'nullable|string|max:255',
            'asset_label_code' => 'nullable|string|max:255',
            'accessories' => 'nullable|string|max:1000',
            'max_price' => 'nullable|string|max:255',
            'min_price' => 'nullable|string|max:255',
            'product_password' => 'nullable|string|max:255',
            'user_reported_issues' => 'nullable|string|max:2000',
            'technician_reported_issues' => 'nullable|string|max:2000',
            'technician_observed_issues' => 'nullable|string|max:2000',
            'user_requested_services' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'شناسه سفارش الزامی است.',
            'order_id.integer' => 'شناسه سفارش باید عدد باشد.',
            'order_id.exists' => 'سفارش مورد نظر یافت نشد.',
            
            'name.required' => 'نام و نام خانوادگی تحویل دهنده الزامی است.',
            'name.string' => 'نام و نام خانوادگی باید متن باشد.',
            'name.max' => 'نام و نام خانوادگی نباید بیشتر از 255 کاراکتر باشد.',
            
            'melicode.required' => 'کد ملی تحویل دهنده الزامی است.',
            'melicode.string' => 'کد ملی باید متن باشد.',
            'melicode.digits' => 'کد ملی باید 10 رقم باشد.',
            
            'product_name.max' => 'نام محصول نباید بیشتر از 255 کاراکتر باشد.',
            'product_brand.max' => 'برند محصول نباید بیشتر از 255 کاراکتر باشد.',
            'product_model.max' => 'مدل محصول نباید بیشتر از 255 کاراکتر باشد.',
            'product_color.max' => 'رنگ محصول نباید بیشتر از 100 کاراکتر باشد.',
            'product_serial_number.max' => 'شماره سریال نباید بیشتر از 255 کاراکتر باشد.',
            'asset_label_code.max' => 'کد لیبل اموال نباید بیشتر از 255 کاراکتر باشد.',
            'accessories.max' => 'لوازم همراه نباید بیشتر از 1000 کاراکتر باشد.',
            'max_price.max' => 'حداکثر قیمت نباید بیشتر از 255 کاراکتر باشد.',
            'min_price.max' => 'حداقل قیمت نباید بیشتر از 255 کاراکتر باشد.',
            'product_password.max' => 'رمز محصول نباید بیشتر از 255 کاراکتر باشد.',
            'user_reported_issues.max' => 'ایرادات گزارش شده توسط کاربر نباید بیشتر از 2000 کاراکتر باشد.',
            'technician_reported_issues.max' => 'ایرادات گزارش شده توسط تکنسین نباید بیشتر از 2000 کاراکتر باشد.',
            'technician_observed_issues.max' => 'ایرادات ظاهری مشاهده شده نباید بیشتر از 2000 کاراکتر باشد.',
            'user_requested_services.max' => 'خدمات درخواستی کاربر نباید بیشتر از 2000 کاراکتر باشد.',
        ];
    }
}
