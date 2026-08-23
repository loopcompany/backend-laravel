<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetUserInPlaceDescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_in_place_description' => 'nullable|string|max:191',
        ];
    }

    public function messages(): array
    {
        return [
            'user_in_place_description.string' => 'The user description on-site must be a string.',
            'user_in_place_description.max' => 'The user description on-site must not exceed 191 characters.',
        ];
    }
}
