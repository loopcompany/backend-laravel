<?php

namespace App\Http\Requests;

use App\Helpers\Helper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class RegistrationRequest extends FormRequest
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
            'melicode' => [
                'required',
                'string',
                'size:10',
                function ($attribute, $value, $fail) {
                    if (!Helper::is_valid_national_code($value)) {
                        $fail(__("The national code entered is not valid."));
                    }

                    // Check if melicode exists for verified user
                    $existingUser = \App\Models\User::where('melicode', $value)->first();
                    if ($existingUser && $existingUser->phone_verified_at) {
                        $fail(__("This national code has already been registered for a verified user."));
                    }

                    // Check if melicode belongs to different unverified phone
                    if ($existingUser && !$existingUser->phone_verified_at && $existingUser->phone != $this->input('phone')) {
                        $fail(__("This national code is registered for another mobile number."));
                    }
                },
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
                function ($attribute, $value, $fail) {
                    // Check if phone exists for verified user
                    $existingUser = \App\Models\User::where('phone', $value)->first();
                    if ($existingUser && $existingUser->phone_verified_at) {
                        $fail(__("This mobile number has already been registered and verified."));
                    }
                    // Allow unverified phones for re-registration
                },
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Check if email exists for verified user
                    $existingUser = \App\Models\User::where('email', $value)->first();
                    if ($existingUser && $existingUser->phone_verified_at) {
                        $fail(__("This email is already registered for a verified user."));
                    }
                    // Allow unverified emails for re-registration
                },
            ],
            'other_referral_code' => [
                'nullable',
                'string',
                'exists:users,referral_code',
                function ($attribute, $value, $fail) {
                    if ($value && $this->input('phone') && \App\Models\User::where('referral_code', $value)->where('phone', $this->input('phone'))->exists()) {
                        $fail(__("You cannot use your own identifier code."));
                    }
                },
            ],
            'province_id' => 'nullable|exists:provinces,id',
            'hashApp' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'region_id' => 'nullable|exists:regions,id',
            'g-recaptcha-response' => [
                function ($attribute, $value, $fail) {
                    // Skip reCAPTCHA validation for API requests (mobile app)
                    if ($this->isApiRequest()) {
                        return;
                    }

                    // For web requests, reCAPTCHA is required
                    if (empty($value)) {
                        $fail('تایید کپچا الزامی است.');
                        return;
                    }

                    // Validate reCAPTCHA response
                    $validator = Validator::make(['g-recaptcha-response' => $value], [
                        'g-recaptcha-response' => 'captcha'
                    ]);

                    if ($validator->fails()) {
                        $fail('کپچا صحیح نیست. لطفاً مجدداً تلاش کنید.');
                    }
                },
            ],
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
            'melicode.required' => 'The national code is required.',
            'melicode.size' => 'The national code must be 10 digits.',
            'melicode.unique' => 'This national code has already been registered.',

            'phone.required' => 'The mobile number is required.',
            'phone.regex' => 'The mobile number format is invalid.',
            'phone.unique' => 'This mobile number has already been registered.',

            'email.required' => 'The email address is required.',
            'email.email' => 'The email format is invalid.',
            'email.unique' => 'This email has already been registered.',

            'other_referral_code.exists' => 'The entered referral code is invalid.',

            'g-recaptcha-response.required' => 'Captcha verification is required.',
            'g-recaptcha-response.captcha' => 'The captcha is incorrect. Please try again.',

        ];
    }

    /**
     * Check if this is an API request (mobile app)
     */
    protected function isApiRequest(): bool
    {
        // Check if request is from API route
        if ($this->is('api/*')) {
            return true;
        }

        // Check Accept header for JSON (mobile app typically sends this)
        if ($this->expectsJson()) {
            return true;
        }

        // Check User-Agent for mobile app identifier
        $userAgent = $this->header('User-Agent', '');
        if (
            str_contains(strtolower($userAgent), 'mobile') ||
            str_contains(strtolower($userAgent), 'app') ||
            str_contains(strtolower($userAgent), 'android') ||
            str_contains(strtolower($userAgent), 'ios')
        ) {
            return true;
        }

        // Check for custom API header
        if (
            $this->header('X-Requested-With') == 'XMLHttpRequest' &&
            $this->header('Content-Type') == 'application/json'
        ) {
            return true;
        }

        return false;
    }
}