<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class LoginRequest extends FormRequest
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
            'phone' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
            ],
            'password' => [
                'required',
                'string',
                'min:6',
            ],
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
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.regex' => 'فرمت شماره موبایل صحیح نیست.',
            'password.required' => 'رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
            'g-recaptcha-response.required' => 'تایید کپچا الزامی است.',
            'g-recaptcha-response.captcha' => 'کپچا صحیح نیست. لطفاً مجدداً تلاش کنید.',
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
        if (str_contains(strtolower($userAgent), 'mobile') || 
            str_contains(strtolower($userAgent), 'app') ||
            str_contains(strtolower($userAgent), 'android') ||
            str_contains(strtolower($userAgent), 'ios')) {
            return true;
        }

        // Check for custom API header
        if ($this->header('X-Requested-With') == 'XMLHttpRequest' && 
            $this->header('Content-Type') == 'application/json') {
            return true;
        }

        return false;
    }
}