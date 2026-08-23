<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FetchMessagesRequest extends FormRequest
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
            'technician_id' => ['required', 'integer', 'exists:technicians,id'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'technician_id.required' => __("Technician ID is required."),
            'technician_id.integer' => __("Technician ID must be a number."),
            'technician_id.exists' => __("Technician not found."),
        ];
    }
}
