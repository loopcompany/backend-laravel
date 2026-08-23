<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetTransactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => 'nullable|date_format:Y-m-d|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'from_date.date_format' => 'فرمت تاریخ شروع باید به صورت Y-m-d باشد (مثال: 2025-01-01).',
            'to_date.date_format' => 'فرمت تاریخ پایان باید به صورت Y-m-d باشد (مثال: 2025-01-31).',
            'to_date.after_or_equal' => 'تاریخ پایان باید بعد از یا برابر با تاریخ شروع باشد.',
            'per_page.integer' => 'تعداد آیتم در هر صفحه باید عدد صحیح باشد.',
            'per_page.min' => 'تعداد آیتم در هر صفحه حداقل باید ۱ باشد.',
            'per_page.max' => 'تعداد آیتم در هر صفحه حداکثر می‌تواند ۱۰۰ باشد.',
        ];
    }
}
