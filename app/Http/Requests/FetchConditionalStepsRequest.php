<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FetchConditionalStepsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
            'fieldId' => ['required', 'integer', 'exists:fields,id'],
            'fieldDetailId' => ['required', 'integer', 'exists:field_details,id'],
        ];
    }
}
