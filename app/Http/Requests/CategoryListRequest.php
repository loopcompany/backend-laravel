<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => 'nullable|integer|exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.integer' => 'شناسه دسته والد باید عدد باشد.',
            'parent_id.exists' => 'دسته والد انتخاب شده وجود ندارد.',
        ];
    }
}