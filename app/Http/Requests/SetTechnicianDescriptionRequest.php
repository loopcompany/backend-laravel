<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetTechnicianDescriptionRequest extends FormRequest
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
            'technician_des' => ['required', 'string', 'max:2000'],
            'date' => ['required', 'string', 'date_format:Y-m-d'],
            'time' => ['required', 'string'],
            'technician_price' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'technician_des.required' => 'The technician description is required.',
            'technician_des.string' => 'The technician description must be a string.',
            'technician_des.max' => 'The technician description must not exceed 2000 characters.',

            'date.required' => 'The date is required.',
            'date.string' => 'The date must be a string.',
            'date.date_format' => 'The date format must be Y-m-d (example: 2025-10-30).',

            'time.required' => 'The time is required.',
            'time.string' => 'The time must be a string.',

            'technician_price.required' => 'The technician price is required.',
            'technician_price.integer' => 'The technician price must be an integer.',
            'technician_price.min' => 'The technician price cannot be negative.',

        ];
    }
}
