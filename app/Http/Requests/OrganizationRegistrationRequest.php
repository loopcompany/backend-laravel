<?php

namespace App\Http\Requests;

use App\Helpers\Helper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationRegistrationRequest extends FormRequest
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
            // Profile Image (Optional)
            'profile_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120', // 5MB
                'dimensions:max_width=2000,max_height=2000',
            ],

            // Organization Information
            'account_type' => [
                'required',
                'string',
                Rule::in(['individual', 'organization', 'company', 'g_organization', 's_g_organization']),

            ],
            'organization_name' => [
                'required',
                'string',
                'min:2',
                'max:191',
            ],
            'agent_name' => [
                'required',
                'string',
                'min:2',
                'max:191',
            ],
            'hashApp' => [
                'nullable',
                'string'
            ],
            'agent_phone' => [
                'required',
            ],
            'business_name' => [
                'nullable',
            ],
            'history' => [
                'required',
                'string',
                'min:2',
                'max:191',
            ],

            'organization_email' => [
                'required',
                'string',
                'email',
                'max:191',
                function ($attribute, $value, $fail) {
                    // بررسی یونیک بودن ایمیل برای کاربران تایید شده
                    $existingUser = \App\Models\User::where('email', $value)
                        ->where('phone_verified_at', '!=', null)
                        ->first();

                    if ($existingUser) {
                        $fail(__("This email is already registered for a verified user."));
                    }
                },
            ],

            'organization_phone' => [
                'required',
                'string',
                'regex:/^0\d{2,3}\d{7,8}$/', // تلفن ثابت ایران
                'min:10',
                'max:15',
            ],

            'organization_address' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],

            // Manager Information
            'manager_full_name' => [
                'required',
                'string',
                'min:2',
                'max:120',
                'regex:/^[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}a-zA-Z\s]+$/u', // فارسی، عربی، لاتین و فاصله
            ],

            'manager_national_code' => [
                'required',
                'string',
                'size:10',
                'regex:/^\d{10}$/',
                function ($attribute, $value, $fail) {
                    // اعتبارسنجی کد ملی ایران
                    if (!Helper::is_valid_national_code($value)) {
                        $fail(__("The administrator's national code is not valid."));
                    }

                    // بررسی یونیک بودن کد ملی مدیر در جدول organizations (فقط سازمان‌های تایید شده)
                    $existingOrg = \App\Models\Organization::whereHas('user', function ($query) {
                        $query->whereNotNull('phone_verified_at');
                    })->where('manager_national_code', $value)->first();

                    if ($existingOrg) {
                        $fail(__("This national code is already registered for another approved organization administrator."));
                    }

                    // همچنین بررسی کنیم که اگر شماره موبایل مدیر فعلی با شماره موبایل سازمان قبلی متفاوت باشد
                    if ($existingOrg && $existingOrg->user && $existingOrg->user->phone !== $this->input('manager_mobile')) {
                        $fail(__("This national code is registered for the manager of another organization with a different mobile number."));
                    }
                },
            ],
            'manager_mobile' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
                function ($attribute, $value, $fail) {
                    // بررسی یونیک بودن شماره موبایل برای کاربران تایید شده
                    $existingUser = \App\Models\User::where('phone', $value)
                        ->where('phone_verified_at', '!=', null)
                        ->first();

                    if ($existingUser) {
                        $fail(__("This mobile number has already been registered and verified."));
                    }
                },
            ],

            'manager_birthdate' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'before:' . now()->subYears(18)->format('Y-m-d'), // حداقل 18 سال
                'after:' . now()->subYears(100)->format('Y-m-d'), // حداکثر 100 سال
            ],

            // Location
            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'region' => [
                'nullable',
                'string',
                'max:50',
            ],

            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
            'region_id' => 'nullable|exists:regions,id',

            'postal_code' => [
                'required',
                'string',
                'regex:/^\d{10}$/', // کد پستی ایران: 10 رقم
                'size:10',
            ],

            // Password
            'password' => [
                'required',
                'string',
                'min:8',
                'max:64',
                // Optionally add regex for strong password
                // 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/',
            ],
            'melicode' => ['required']
        ];
    }

    /**
     * Get custom messages for validator errors (Persian)
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Profile Image
            'profile_image.image' => __("The uploaded file must be an image."),
            'profile_image.mimes' => __("The image format must be jpg, jpeg, png or webp."),
            'profile_image.max' => __("The image size should not exceed 5 MB."),
            'profile_image.dimensions' => __("The image dimensions should not exceed 2000 x 2000 pixels."),

            // Organization
            'organization_name.required' => __("Organization name is required."),
            'organization_name.min' => __("The organization name must be at least 2 characters."),
            'organization_name.max' => __("The organization name should not exceed 200 characters."),

            'organization_email.required' => __("Organization email is required."),
            'organization_email.email' => __("The email format is invalid."),

            'organization_phone.required' => __("The organization's landline phone number is required."),
            'organization_phone.regex' => __("The landline number format is invalid."),

            'organization_address.required' => __("Organization address is required."),
            'organization_address.min' => __("The organization address must be at least 10 characters long."),
            'organization_address.max' => __("The organization address should not exceed 1000 characters."),

            // Manager
            'manager_full_name.required' => __("The administrator's first and last name are required."),
            'manager_full_name.min' => 'The manager name must be at least 2 characters long.',
            'manager_full_name.regex' => 'The manager name may only contain Persian, Arabic, Latin letters and spaces.',

            'manager_national_code.required' => 'The manager national code is required.',
            'manager_national_code.size' => 'The national code must be exactly 10 digits.',
            'manager_national_code.regex' => 'The national code must contain only numbers.',

            'manager_mobile.required' => 'The manager mobile number is required.',
            'manager_mobile.regex' => 'The mobile number format is invalid. (Example: 09121234567)',

            'manager_birthdate.required' => 'The manager birthdate is required.',
            'manager_birthdate.date' => 'The birthdate format is invalid.',
            'manager_birthdate.date_format' => 'The birthdate format must be YYYY-MM-DD.',
            'manager_birthdate.before' => 'The manager must be at least 18 years old.',
            'manager_birthdate.after' => 'The birthdate is invalid.',

            // Location
            'city.required' => 'The city is required.',
            'city.string' => 'The city must be a string.',
            'city.max' => 'The city name may not be greater than 100 characters.',

            'region.required' => 'The region is required.',
            'region.string' => 'The region must be a string.',
            'region.max' => 'The region name may not be greater than 50 characters.',

            'province_id.exists' => 'The selected province is invalid.',
            'city_id.exists' => 'The selected city is invalid.',
            'region_id.exists' => 'The selected region is invalid.',

            'postal_code.required' => 'The postal code is required.',
            'postal_code.regex' => 'The postal code must be 10 digits without hyphens.',
            'postal_code.size' => 'The postal code must be exactly 10 digits.',

            // Password
            'password.required' => 'The password is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.max' => 'The password may not be greater than 64 characters.',
        ];
    }
}
