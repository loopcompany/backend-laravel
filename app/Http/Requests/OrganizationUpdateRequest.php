<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\Helper;

class OrganizationUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // فقط کاربران احراز هویت شده که اکانت سازمانی دارند می‌توانند استفاده کنند
        return auth()->check() && auth()->user()->isOrganization();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // فیلدهای جدول organizations
            'organization_name' => 'sometimes|required|string|max:255',
            'agent_name' => 'sometimes|required|string|max:191',
            'agent_phone' => 'sometimes|required',
            'business_name' => 'nullable',
            'history' => 'sometimes|required|string|max:191',
            'organization_code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                'unique:organizations,organization_code,' . auth()->user()->organization?->id
            ],
            'organization_phone' => [
                'sometimes',
                'required',
                'regex:/^0[0-9]{10}$/',
            ],
            'organization_address' => 'sometimes|required|string|max:500',
            'manager_full_name' => 'sometimes|required|string|max:255',
            'manager_national_code' => [
                'sometimes',
                'required',
                'regex:/^[0-9]{10}$/',
                function ($attribute, $value, $fail) {
                    if (!Helper::is_valid_national_code($value)) {
                        $fail(__("The national code entered is not valid."));
                    }
                },
            ],
            'profile_image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:5120',

            // فیلدهای جدول users
            'organization_email' => 'sometimes|nullable|email|max:255',
            'manager_mobile' => [
                'sometimes',
                'nullable',
                'regex:/^09[0-9]{9}$/',
            ],
            'manager_birthdate' => [
                'nullable'
            ],
            'city' => 'sometimes|nullable|string|max:100',
            'region' => 'sometimes|nullable|string|max:100',
            'postal_code' => [
                'sometimes',
                'nullable',
                'regex:/^[0-9]{10}$/',
            ],
            'province_id' => 'sometimes|nullable|exists:provinces,id',
            'city_id' => 'sometimes|nullable|exists:cities,id',
            'region_id' => 'sometimes|nullable|exists:regions,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // پیام‌های جدول organizations
            'organization_name.required' => 'The organization name is required.',
            'organization_name.string' => 'The organization name must be a string.',
            'organization_name.max' => 'The organization name must be a maximum of 255 characters.',

            'agent_name.required' => 'The agent name is required.',
            'agent_name.string' => 'The agent name must be a string.',
            'agent_name.max' => 'The agent name must be a maximum of 191 characters.',

            'agent_phone.required' => 'The agent phone number is required.',

            'history.required' => 'The organization history is required.',
            'history.string' => 'The organization history must be a string.',
            'history.max' => 'The organization history must be a maximum of 191 characters.',

            'organization_code.required' => 'The organization code is required.',
            'organization_code.unique' => 'This organization code has already been registered.',
            'organization_code.max' => 'The organization code must be a maximum of 20 characters.',

            'organization_phone.required' => 'The organization phone number is required.',
            'organization_phone.regex' => 'The phone number must be 11 digits and start with 0.',

            'organization_address.required' => 'The organization address is required.',
            'organization_address.max' => 'The organization address must be a maximum of 500 characters.',

            'manager_full_name.required' => 'The full name of the manager is required.',
            'manager_full_name.max' => 'The full name of the manager must be a maximum of 255 characters.',

            'manager_national_code.required' => 'The manager national code is required.',
            'manager_national_code.regex' => 'The national code must be 10 digits.',

            'profile_image.image' => 'The file must be an image.',
            'profile_image.mimes' => 'The image format must be jpeg, png, jpg, or gif.',
            'profile_image.max' => 'The image size should not be more than 5 MB.',

            'organization_email.email' => 'The email format is invalid.',
            'organization_email.max' => 'The email must be a maximum of 255 characters.',

            'manager_mobile.regex' => 'The manager mobile number must start with 09 and be 11 digits.',

            'city.max' => 'The city name must be a maximum of 100 characters.',
            'region.max' => 'The region must be a maximum of 100 characters.',
            'postal_code.regex' => 'The postal code must be 10 digits.',

        ];
    }
}
