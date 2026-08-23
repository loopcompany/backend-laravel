<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|array|min:1',
            'file.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'فایل الزامی است.',
            'file.array' => 'فایل‌ها باید به صورت آرایه ارسال شوند.',
            'file.min' => 'حداقل یک فایل باید ارسال شود.',
            
            'file.*.file' => 'فایل ارسالی معتبر نیست.',
            'file.*.mimes' => 'فرمت فایل باید JPG، PNG، PDF، DOC یا DOCX باشد.',
            'file.*.max' => 'حجم هر فایل نباید بیش از 10 مگابایت باشد.',
        ];
    }
}
