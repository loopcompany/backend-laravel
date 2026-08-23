<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetEmergencyHelpRequest extends FormRequest
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
            'emergency_help' => ['required', 'string', 'max:1000'],
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
            'emergency_help.required' => 'The emergency help request field is required.',
            'emergency_help.string' => 'The emergency help request field must be a string.',
            'emergency_help.max' => 'The emergency help request field must not exceed 1000 characters.',

        ];
    }
}
