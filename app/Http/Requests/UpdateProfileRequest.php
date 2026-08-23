<?php

namespace App\Http\Requests;

use App\Helpers\Helper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $userId = $this->user()->id;
        
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[\x{0600}-\x{06FF}\x{200C}\x{200D}\s]+$/u'
            ],
            'last_name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[\x{0600}-\x{06FF}\x{200C}\x{200D}\s]+$/u'
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'melicode' => [
                'sometimes',
                'required',
                'string',
                'size:10',
                'regex:/^[0-9]{10}$/',
                function ($attribute, $value, $fail) {
                    if (!Helper::is_valid_national_code($value)) {
                        $fail('کد ملی وارد شده نامعتبر است.');
                    }
                },
            ],
            'password' => [
                'sometimes',
                'required',
                'string',
                'min:8',
                'max:255',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).*$/'
            ],
            'current_password' => [
                'required_with:password',
                'string',
                function ($attribute, $value, $fail) {
                    if ($this->has('password') && !password_verify($value, $this->user()->password)) {
                        $fail('رمز عبور فعلی اشتباه است.');
                    }
                }
            ],
            'g-recaptcha-response' => [
                function ($attribute, $value, $fail) {
                    // Skip reCAPTCHA validation for API requests (mobile app)
                    if ($this->isApiRequest()) {
                        return;
                    }
                    
                    // For web requests, reCAPTCHA is required only when changing password
                    if ($this->filled('password')) {
                        if (empty($value)) {
                            $fail('برای تغییر رمز عبور، تایید کپچا الزامی است.');
                            return;
                        }
                        
                        // Validate reCAPTCHA response
                        $validator = Validator::make(['g-recaptcha-response' => $value], [
                            'g-recaptcha-response' => 'captcha'
                        ]);
                        
                        if ($validator->fails()) {
                            $fail('کپچا صحیح نیست. لطفاً مجدداً تلاش کنید.');
                        }
                    }
                },
            ],
            'birth_date' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^(1[3-4][0-9][0-9])\/(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])$/',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        // بررسی اعتبار تاریخ شمسی
                        $parts = explode('/', $value);
                        if (count($parts) == 3) {
                            $year = (int)$parts[0];
                            $month = (int)$parts[1];
                            $day = (int)$parts[2];
                            
                            // بررسی محدوده سال (1300 تا 1450)
                            if ($year < 1300 || $year > 1450) {
                                $fail('سال تولد نامعتبر است.');
                                return;
                            }
                            
                            // بررسی روز برای هر ماه
                            if ($month <= 6 && $day > 31) {
                                $fail('روز تولد برای این ماه نامعتبر است.');
                            } elseif ($month > 6 && $month <= 11 && $day > 30) {
                                $fail('روز تولد برای این ماه نامعتبر است.');
                            } elseif ($month == 12 && $day > 29) {
                                $fail('روز تولد برای ماه اسفند نامعتبر است.');
                            }
                        }
                    }
                }
            ],
            'mobile_number' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^09[0-9]{9}$/'
            ],
            'phone_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:20'
            ],
            'postal_code' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^[0-9]{10}$/'
            ],
            'city' => [
                'sometimes',
                'nullable',
                'string',
                'min:2',
                'max:50',
                'regex:/^[\x{0600}-\x{06FF}\x{200C}\x{200D}\s]+$/u'
            ],
            'region_id' => [
                'sometimes',
                'nullable',
            ],
            'region' => [
                'sometimes',
                'nullable',
            ],
            'home_address' => [
                'sometimes',
                'nullable',
                'string',
                'min:5',
                'max:191'
            ],
            'work_address' => [
                'sometimes',
                'nullable',
                'string',
                'min:5',
                'max:191'
            ],
            'card_number' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^[0-9]{16}$/'
            ],
            'sheba_number' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^IR[0-9]{24}$/'
            ],
            'profile_photo_path' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120'
            ]
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'نام الزامی است.',
            'name.string' => 'نام باید متن باشد.',
            'name.min' => 'نام باید حداقل 2 کاراکتر باشد.',
            'name.max' => 'نام نباید بیشتر از 50 کاراکتر باشد.',
            'name.regex' => 'نام فقط می‌تواند شامل حروف فارسی باشد.',
            
            'last_name.required' => 'نام خانوادگی الزامی است.',
            'last_name.string' => 'نام خانوادگی باید متن باشد.',
            'last_name.min' => 'نام خانوادگی باید حداقل 2 کاراکتر باشد.',
            'last_name.max' => 'نام خانوادگی نباید بیشتر از 50 کاراکتر باشد.',
            'last_name.regex' => 'نام خانوادگی فقط می‌تواند شامل حروف فارسی باشد.',
            
            'email.email' => 'فرمت ایمیل نامعتبر است.',
            'email.max' => 'ایمیل نباید بیشتر از 255 کاراکتر باشد.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            
            'melicode.required' => 'کد ملی الزامی است.',
            'melicode.string' => 'کد ملی باید متن باشد.',
            'melicode.size' => 'کد ملی باید دقیقاً 10 رقم باشد.',
            'melicode.regex' => 'کد ملی فقط می‌تواند شامل اعداد باشد.',
            
            'password.required' => 'رمز عبور الزامی است.',
            'password.string' => 'رمز عبور باید متن باشد.',
            'password.min' => 'رمز عبور باید حداقل 8 کاراکتر باشد.',
            'password.max' => 'رمز عبور نباید بیشتر از 255 کاراکتر باشد.',
            'password.regex' => 'رمز عبور باید شامل حداقل یک حرف کوچک، یک حرف بزرگ و یک عدد باشد.',
            
            'current_password.required_with' => 'برای تغییر رمز عبور، وارد کردن رمز عبور فعلی الزامی است.',
            'current_password.string' => 'رمز عبور فعلی باید متن باشد.',
            
            'g-recaptcha-response.required_with' => 'برای تغییر رمز عبور، تایید کپچا الزامی است.',
            'g-recaptcha-response.captcha' => 'کپچا صحیح نیست. لطفاً مجدداً تلاش کنید.',
            
            'birth_date.string' => 'تاریخ تولد باید متن باشد.',
            'birth_date.regex' => 'فرمت تاریخ تولد نامعتبر است. (مثال: 1370/05/15)',
            
            'mobile_number.string' => 'شماره موبایل باید متن باشد.',
            'mobile_number.regex' => 'فرمت شماره موبایل نامعتبر است. (مثال: 09123456789)',
            
            'phone_number.string' => 'شماره تلفن باید متن باشد.',
            'phone_number.max' => 'شماره تلفن نباید بیشتر از 20 کاراکتر باشد.',
            
            'postal_code.string' => 'کد پستی باید متن باشد.',
            'postal_code.regex' => 'کد پستی باید دقیقاً 10 رقم باشد.',
            
            'city.string' => 'نام شهر باید متن باشد.',
            'city.min' => 'نام شهر باید حداقل 2 کاراکتر باشد.',
            'city.max' => 'نام شهر نباید بیشتر از 50 کاراکتر باشد.',
            'city.regex' => 'نام شهر فقط می‌تواند شامل حروف فارسی باشد.',
            
            
            
            'home_address.string' => 'آدرس منزل باید متن باشد.',
            'home_address.min' => 'آدرس منزل باید حداقل 10 کاراکتر باشد.',
            'home_address.max' => 'آدرس منزل نباید بیشتر از 500 کاراکتر باشد.',
            
            'work_address.string' => 'آدرس محل کار باید متن باشد.',
            'work_address.min' => 'آدرس محل کار باید حداقل 10 کاراکتر باشد.',
            'work_address.max' => 'آدرس محل کار نباید بیشتر از 500 کاراکتر باشد.',
            
            'card_number.string' => 'شماره کارت باید متن باشد.',
            'card_number.regex' => 'شماره کارت باید دقیقاً 16 رقم باشد.',
            
            'sheba_number.string' => 'شماره شبا باید متن باشد.',
            'sheba_number.regex' => 'فرمت شماره شبا نامعتبر است. (مثال: IR123456789012345678901234)'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'email' => 'ایمیل',
            'melicode' => 'کد ملی',
            'password' => 'رمز عبور',
            'current_password' => 'رمز عبور فعلی',
            'birth_date' => 'تاریخ تولد',
            'mobile_number' => 'شماره موبایل',
            'phone_number' => 'شماره تلفن',
            'postal_code' => 'کد پستی',
            'city' => 'شهر',
            'region_id' => 'منطقه',
            'home_address' => 'آدرس منزل',
            'work_address' => 'آدرس محل کار',
            'card_number' => 'شماره کارت',
            'sheba_number' => 'شماره شبا',
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