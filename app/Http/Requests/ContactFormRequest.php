<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
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
            'name' => 'required|string|max:255|min:2',
            'email' => 'required|email|max:255',
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^(\+98|0)?9[0-9]{9}$/', // Iranian mobile format
            ],
            'title' => 'required|string|max:255|min:5',
            'message' => 'required|string|max:1000|min:10',
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
            'name.required' => __("First and last name are required."),
            'name.min' => __("The name must be at least 2 characters."),
            'name.max' => __("The name cannot be longer than 255 characters."),
            
            'email.required' => __("Email is required."),
            'email.email' => __("The email format is not correct."),
            'email.max' => __("Email cannot be longer than 255 characters."),
            
            'phone.required' => __("Phone number is required."),
            'phone.regex' => __("The mobile number format is not correct. (Example: 09123456789)"),
            'phone.max' => __("The phone number cannot be longer than 20 characters."),
            
            'title.required' => __("Title is required."),
            'title.min' => __("The title must be at least 5 characters."),
            'title.max' => __("The title cannot be longer than 255 characters."),
            
            'message.required' => __("Message text is required."),
            'message.min' => __("The message must be at least 10 characters long."),
            'message.max' => __("The message cannot exceed 1000 characters."),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'نام و نام خانوادگی',
            'email' => 'ایمیل',
            'phone' => 'شماره موبایل',
            'title' => 'عنوان',
            'message' => 'پیام',
        ];
    }
}
