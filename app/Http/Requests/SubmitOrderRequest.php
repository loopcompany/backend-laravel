<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_id' => 'required|integer|exists:user_addresses,id',
            'category_id' => 'required|integer|exists:categories,id',
            'total_price' => 'required|numeric|min:0',
            'is_urgent' => 'nullable|boolean',
            'is_fixed' => 'nullable|boolean',
            'image_path' => 'nullable|string',
            'description' => 'nullable|string|max:1000',
            'date' => 'required|date_format:Y-m-d',
            'time' => 'nullable|string',
            'female_count' => 'nullable|integer|min:0',
            'male_count' => 'nullable|integer|min:0',
            'unspecified_count' => 'nullable|integer|min:0',
            'discount_code' => 'nullable|string|max:50',
            'steps' => 'nullable|array',
            'file_paths' => 'nullable|array',
            'platform' => 'required|string',
            
            // Service Schedule (for organizations)
            'service_schedule' => 'nullable|array',
            'service_schedule.type' => 'nullable|string|in:long_term,short_term',
            'service_schedule.long_term' => 'nullable|array',
            'service_schedule.long_term.duration' => 'nullable|string|max:50',
            'service_schedule.long_term.date' => 'nullable|date_format:Y-m-d',
            'service_schedule.long_term.time' => 'nullable|string|max:50',
            'service_schedule.long_term.file' => 'nullable|string',
            'service_schedule.short_term' => 'nullable|array',
            'service_schedule.short_term.date' => 'nullable|date_format:Y-m-d',
            'service_schedule.short_term.time' => 'nullable|string|max:50',
            'service_schedule.short_term.file' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'address_id.required' => 'آدرس الزامی است.',
            'address_id.exists' => 'آدرس انتخاب شده معتبر نیست.',
            
            'category_id.required' => 'دسته‌بندی الزامی است.',
            'category_id.exists' => 'دسته‌بندی انتخاب شده معتبر نیست.',
            
            'total_price.required' => 'قیمت کل الزامی است.',
            'total_price.numeric' => 'قیمت کل باید عدد باشد.',
            'total_price.min' => 'قیمت کل نمی‌تواند منفی باشد.',
            
            'is_urgent.boolean' => 'فیلد فوری باید true یا false باشد.',
            'is_fixed.boolean' => 'فیلد ثابت باید true یا false باشد.',
            
            'description.max' => 'توضیحات نباید بیش از 1000 کاراکتر باشد.',
            
            'date.required' => 'تاریخ الزامی است.',
            'date.date_format' => 'فرمت تاریخ صحیح نیست (Y-m-d).',
            
            'time.required' => 'زمان الزامی است.',
            
            'female_count.integer' => 'تعداد تکنسین زن باید عدد صحیح باشد.',
            'female_count.min' => 'تعداد تکنسین زن نمی‌تواند منفی باشد.',
            
            'male_count.integer' => 'تعداد تکنسین مرد باید عدد صحیح باشد.',
            'male_count.min' => 'تعداد تکنسین مرد نمی‌تواند منفی باشد.',
            
            'unspecified_count.integer' => 'تعداد تکنسین نامشخص باید عدد صحیح باشد.',
            'unspecified_count.min' => 'تعداد تکنسین نامشخص نمی‌تواند منفی باشد.',
            
            'discount_code.max' => 'کد تخفیف نباید بیش از 50 کاراکتر باشد.',
            
            'steps.array' => 'مراحل سفارش باید به صورت آرایه ارسال شوند.',
            
            // Service Schedule validation messages
            'service_schedule.array' => 'زمان نگهداری و سرویس باید به صورت آرایه ارسال شود.',
            'service_schedule.type.in' => 'نوع زمان‌بندی باید long_term یا short_term باشد.',
            'service_schedule.long_term.array' => 'اطلاعات بلندمدت باید به صورت آرایه ارسال شود.',
            'service_schedule.long_term.duration.max' => 'مدت زمان نباید بیش از 50 کاراکتر باشد.',
            'service_schedule.long_term.date.date_format' => 'فرمت تاریخ بلندمدت صحیح نیست (Y-m-d).',
            'service_schedule.long_term.time.max' => 'زمان نباید بیش از 50 کاراکتر باشد.',
            'service_schedule.short_term.array' => 'اطلاعات کوتاه‌مدت باید به صورت آرایه ارسال شود.',
            'service_schedule.short_term.date.date_format' => 'فرمت تاریخ کوتاه‌مدت صحیح نیست (Y-m-d).',
            'service_schedule.short_term.time.max' => 'زمان نباید بیش از 50 کاراکتر باشد.',
        ];
    }
}
