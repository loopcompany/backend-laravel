<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetUserFinalDescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'The final description is required.',
            'description.string' => 'The final description must be a string.',
            'description.max' => 'The final description must not exceed 2000 characters.',
        ];
    }
}
