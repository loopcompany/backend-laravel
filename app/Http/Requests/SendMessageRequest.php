<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
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
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'technician_id.required' => 'The technician ID is required.',
            'technician_id.integer' => 'The technician ID must be an integer.',
            'technician_id.exists' => 'The technician was not found.',

            'message.required' => 'The message body is required.',
            'message.string' => 'The message must be a string.',
            'message.max' => 'The message must not exceed 5000 characters.',

        ];
    }
}
