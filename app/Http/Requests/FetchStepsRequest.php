<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FetchStepsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
        ];
    }
}
