<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TechnicianRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:191',
            'hashApp' => 'nullable|string',
            'melicode' => 'nullable|string|size:10|regex:/^[0-9]{10}$/',
            'phone' => [
                'required',
                'string',
                'regex:/^09[0-9]{9}$/',
            ],
            'birth_date' => 'nullable',
            'father_name' => 'required|string|max:191',
            'issued_from' => 'required|string|max:191',
            'serial_number' => 'required|string|max:50',
            'marital_status' => 'required|in:مجرد,متأهل',
            'military_status' => 'required|in:مشمول خدمت,درانتظار اعزام,فاقد سابقه خدمت,اتمام خدمت,معافیت,در حال تحصیل',
            'education_status' => 'required|string|max:191',
            'education_field' => 'required|string|max:191',
            'telephone' => 'required|string|max:20',
            'vehicle_type' => 'nullable|string|max:191',
            'home_postal_code' => 'required|string|size:10|regex:/^[0-9]{10}$/',
            'region' => 'required|string|max:191',
            'city' => 'required|string|max:191',
            'home_address' => 'required|string|max:1000',
            'other_referral_code' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'idea' => 'required|string|max:1000',
            'software_skill' => 'required|string|max:1000',
            'hardware_skill' => 'required|string|max:1000',
            'software_weakness' => 'required|string|max:1000',
            'hardware_weakness' => 'required|string|max:1000',
            'resume' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg|max:10240',
            'resume_photo' => 'nullable|image|mimes:jpg,jpeg|max:5120',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'expertise_ids' => 'nullable|array',
            'expertise_ids.*' => 'integer|exists:expertises,id',
            'certificate_issue_date'=> 'nullable|string|max:191',
            'certificate_number'=> 'nullable|string|max:191',
            'certificate_expiry_date'=> 'nullable|string|max:191',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام الزامی است.',
            'name.max' => 'نام نباید بیش از 191 کاراکتر باشد.',
            
            'email.email' => 'فرمت ایمیل صحیح نیست.',
            'email.max' => 'ایمیل نباید بیش از 191 کاراکتر باشد.',
            
            'melicode.size' => 'کد ملی باید 10 رقم باشد.',
            'melicode.regex' => 'کد ملی باید فقط شامل اعداد باشد.',
            
            'phone.required' => 'شماره تلفن الزامی است.',
            'phone.regex' => 'شماره تلفن باید با 09 شروع شده و 11 رقم باشد.',
            'phone.unique' => 'این شماره تلفن قبلاً ثبت شده است.',
            
            'birth_date.date_format' => 'فرمت تاریخ تولد صحیح نیست (Y-m-d).',
            
            'father_name.required' => 'نام پدر الزامی است.',
            'father_name.max' => 'نام پدر نباید بیش از 191 کاراکتر باشد.',
            
            'issued_from.required' => 'محل صدور الزامی است.',
            'issued_from.max' => 'محل صدور نباید بیش از 191 کاراکتر باشد.',
            
            'serial_number.required' => 'شماره شناسنامه الزامی است.',
            'serial_number.max' => 'شماره شناسنامه نباید بیش از 50 کاراکتر باشد.',
            
            'marital_status.required' => 'وضعیت تأهل الزامی است.',
            'marital_status.in' => 'وضعیت تأهل باید مجرد یا متأهل باشد.',
            
            'military_status.required' => 'وضعیت نظام وظیفه الزامی است.',
            'military_status.in' => 'وضعیت نظام وظیفه نامعتبر است.',
            
            'education_status.required' => 'وضعیت تحصیلات الزامی است.',
            'education_status.max' => 'وضعیت تحصیلات نباید بیش از 191 کاراکتر باشد.',
            'education_field.required' => 'رشته تحصیلی الزامی است.',
            'education_field.max' => 'رشته تحصیلی نباید بیش از 191 کاراکتر باشد.',
            
            'telephone.required' => 'شماره تلفن ثابت الزامی است.',
            'telephone.max' => 'شماره تلفن ثابت نباید بیش از 20 کاراکتر باشد.',
            
            
            'vehicle_type.required' => 'نوع وسیله نقلیه الزامی است.',
            'vehicle_type.max' => 'نوع وسیله نقلیه نباید بیش از 191 کاراکتر باشد.',
            
            'home_postal_code.required' => 'کد پستی منزل الزامی است.',
            'home_postal_code.size' => 'کد پستی باید 10 رقم باشد.',
            'home_postal_code.regex' => 'کد پستی باید فقط شامل اعداد باشد.',
            
            'region.required' => 'منطقه الزامی است.',
            'region.max' => 'منطقه نباید بیش از 191 کاراکتر باشد.',
            
            'city.required' => 'شهر الزامی است.',
            'city.max' => 'شهر نباید بیش از 191 کاراکتر باشد.',
            
            'home_address.required' => 'آدرس منزل الزامی است.',
            'home_address.max' => 'آدرس منزل نباید بیش از 1000 کاراکتر باشد.',
            
            'other_referral_code.max' => 'کد پرسنلی دیگران نباید بیش از 50 کاراکتر باشد.',
            
            'idea.required' => 'ایده/خلاقیت الزامی است.',
            'idea.max' => 'ایده/خلاقیت نباید بیش از 1000 کاراکتر باشد.',
            
            'software_skill.required' => 'تسلط نرم‌افزاری الزامی است.',
            'software_skill.max' => 'تسلط نرم‌افزاری نباید بیش از 1000 کاراکتر باشد.',
            
            'hardware_skill.required' => 'تسلط سخت‌افزاری الزامی است.',
            'hardware_skill.max' => 'تسلط سخت‌افزاری نباید بیش از 1000 کاراکتر باشد.',
            
            'software_weakness.required' => 'نقاط ضعف نرم‌افزاری الزامی است.',
            'software_weakness.max' => 'نقاط ضعف نرم‌افزاری نباید بیش از 1000 کاراکتر باشد.',
            
            'hardware_weakness.required' => 'نقاط ضعف سخت‌افزاری الزامی است.',
            'hardware_weakness.max' => 'نقاط ضعف سخت‌افزاری نباید بیش از 1000 کاراکتر باشد.',
            
            'resume.file' => 'رزومه باید یک فایل باشد.',
            'resume.mimes' => 'رزومه باید فرمت PDF، DOC، DOCX، JPG یا JPEG باشد.',
            'resume.max' => 'حجم فایل رزومه نباید بیش از 10 مگابایت باشد.',
            
            'resume_photo.image' => 'عکس رزومه باید یک تصویر باشد.',
            'resume_photo.mimes' => 'عکس رزومه باید فرمت JPG یا JPEG باشد.',
            'resume_photo.max' => 'حجم عکس رزومه نباید بیش از 5 مگابایت باشد.',
            
            'profile_image.image' => 'عکس پروفایل باید یک تصویر باشد.',
            'profile_image.mimes' => 'عکس پروفایل باید فرمت JPG، JPEG یا PNG باشد.',
            'profile_image.max' => 'حجم عکس پروفایل نباید بیش از 5 مگابایت باشد.',
            
            'expertise_ids.array' => 'تخصص‌ها باید به صورت آرایه ارسال شوند.',
            'expertise_ids.*.integer' => 'شناسه تخصص باید عدد صحیح باشد.',
            'expertise_ids.*.exists' => 'تخصص انتخاب شده معتبر نیست.',
        ];
    }
}