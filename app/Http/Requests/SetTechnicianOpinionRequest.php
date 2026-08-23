<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetTechnicianOpinionRequest extends FormRequest
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
            'technician_opinion' => 'required|string|max:2000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'technician_opinion.required' => 'The technician opinion is required.',
            'technician_opinion.string' => 'The technician opinion must be a string.',
            'technician_opinion.max' => 'The technician opinion must not exceed 2000 characters.',

        ];
    }
}
