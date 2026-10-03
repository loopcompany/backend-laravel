<?php

namespace App\Support\Careers;

/**
 * همه‌ی گزینه‌های فرم «همکاری با لوپ»، فرم گزینش و مصاحبه و پرونده‌ی استخدامی
 * (سند «صفحه همکاری در سایت»). کلیدها در دیتابیس ذخیره می‌شوند و برچسب‌ها فقط برای نمایش‌اند.
 */
final class CareerOptions
{
    // ----------------------------------------------------------------- ۱. اطلاعات فردی

    public const GENDERS = ['male' => 'آقا', 'female' => 'خانم'];

    public const MARITAL = ['single' => 'مجرد', 'married' => 'متأهل'];

    public const MILITARY = [
        'completed' => 'پایان خدمت',
        'exempt' => 'معاف',
        'serving' => 'در حال خدمت',
        'other' => 'سایر',
    ];

    // ----------------------------------------------------------------- ۲. موقعیت شغلی

    public const JOB_TITLES = [
        'field_technician' => 'تکنسین میدانی',
        'internal_technician' => 'تکنسین اداری (داخلی)',
        'office_staff' => 'کارمند اداری',
        'sales' => 'کارشناس فروش',
        'warehouse' => 'کارشناس انبار',
        'accounting' => 'کارشناس حسابداری',
        'other' => 'سایر',
    ];

    public const COOPERATION_TYPES = [
        'full_time' => 'تمام‌وقت',
        'part_time' => 'پاره‌وقت',
        'contract' => 'قراردادی',
        'internship' => 'کارآموزی',
    ];

    // ----------------------------------------------------------------- ۳. تحصیلات و سابقه

    public const EDUCATION = [
        'below_diploma' => 'زیر دیپلم',
        'diploma' => 'دیپلم',
        'associate' => 'کاردانی',
        'bachelor' => 'کارشناسی',
        'master' => 'کارشناسی ارشد',
        'phd' => 'دکتری',
        'other' => 'سایر',
    ];

    public const WORK_EXPERIENCE = [
        'none' => 'بدون سابقه',
        'lt1' => 'کمتر از ۱ سال',
        '1_3' => '۱ تا ۳ سال',
        '3_5' => '۳ تا ۵ سال',
        '5_10' => '۵ تا ۱۰ سال',
        'gt10' => 'بیشتر از ۱۰ سال',
    ];

    public const RELATED_EXPERIENCE = [
        'none' => 'بدون سابقه',
        'lt1' => 'کمتر از ۱ سال',
        '1_3' => '۱ تا ۳ سال',
        '3_5' => '۳ تا ۵ سال',
        'gt5' => 'بیشتر از ۵ سال',
    ];

    // ----------------------------------------------------------------- ۴. مهارت‌ها

    public const SKILL_LEVELS = [
        'basic' => 'مقدماتی',
        'intermediate' => 'متوسط',
        'advanced' => 'پیشرفته',
        'none' => 'بدون مهارت',
    ];

    public const SKILL_LEVEL_HINTS = [
        'basic' => 'آشنایی اولیه',
        'intermediate' => 'توانایی انجام امور معمول به‌صورت مستقل',
        'advanced' => 'توانایی عیب‌یابی، حل مسائل تخصصی و انجام مستقل کار',
    ];

    public const SKILL_GROUPS = [
        'hardware' => [
            'title' => 'سخت‌افزار کامپیوتر',
            'skills' => [
                'hw_parts' => 'شناخت قطعات کامپیوتر',
                'hw_assemble' => 'اسمبل و سرهم‌بندی کامپیوتر',
                'hw_troubleshoot' => 'عیب‌یابی سخت‌افزاری',
                'hw_laptop_repair' => 'تعمیر لپ‌تاپ',
                'hw_pc_aio_repair' => 'تعمیر کامپیوتر و AIO',
                'hw_motherboard' => 'تعمیر و تعویض مادربرد',
                'hw_laptop_parts' => 'تعمیر و تعویض قطعات لپ‌تاپ',
                'hw_ram' => 'شناخت و تعویض RAM',
                'hw_storage' => 'شناخت و تعویض HDD / SSD / M.2',
                'hw_psu' => 'عیب‌یابی منبع تغذیه',
                'hw_monitor' => 'تعمیر مانیتور',
                'hw_printer' => 'تعمیر پرینتر',
                'hw_copier' => 'تعمیر دستگاه کپی صنعتی',
                'hw_peripherals' => 'عیب‌یابی تجهیزات جانبی',
                'hw_soldering' => 'لحیم‌کاری و تعمیرات برد',
            ],
        ],
        'software' => [
            'title' => 'نرم‌افزار و سیستم‌عامل',
            'skills' => [
                'sw_windows_install' => 'نصب ویندوز',
                'sw_drivers' => 'نصب و راه‌اندازی درایورها',
                'sw_windows_troubleshoot' => 'عیب‌یابی ویندوز',
                'sw_apps' => 'نصب نرم‌افزارهای کاربردی',
                'sw_malware' => 'ویروس‌یابی و رفع بدافزار',
                'sw_backup' => 'پشتیبان‌گیری و بازیابی اطلاعات',
                'sw_recovery' => 'بازیابی اطلاعات',
                'sw_network_setup' => 'نصب و راه‌اندازی شبکه',
                'sw_network_troubleshoot' => 'عیب‌یابی مشکلات اینترنت و شبکه',
                'sw_linux' => 'آشنایی با Linux',
                'sw_enterprise' => 'نصب و راه‌اندازی نرم‌افزارهای سازمانی',
                'sw_support' => 'پشتیبانی نرم‌افزاری کاربران',
            ],
        ],
        'office' => [
            'title' => 'مهارت‌های اداری و عمومی',
            'skills' => [
                'of_word' => 'Microsoft Word',
                'of_excel' => 'Microsoft Excel',
                'of_outlook' => 'Microsoft Outlook',
                'of_typing' => 'تایپ فارسی و انگلیسی',
                'of_accounting_sw' => 'کار با نرم‌افزارهای حسابداری',
                'of_crm' => 'کار با CRM و اتوماسیون',
                'of_customer' => 'پاسخ‌گویی و ارتباط با مشتری',
                'of_sales' => 'فروش و مذاکره',
                'of_reporting' => 'ثبت گزارش و مستندسازی',
            ],
        ],
    ];

    // ----------------------------------------------------------------- ۵. تخصص‌های تکمیلی

    public const INTEREST_AREAS = [
        'laptop' => 'لپ‌تاپ',
        'pc_aio' => 'کامپیوتر و AIO',
        'monitor' => 'مانیتور',
        'printer' => 'پرینتر',
        'copier' => 'دستگاه کپی صنعتی',
        'network' => 'شبکه',
        'peripherals' => 'تجهیزات جانبی',
        'hardware' => 'سخت‌افزار',
        'software' => 'نرم‌افزار',
        'sales_supply' => 'فروش و تأمین کالا',
        'office' => 'امور اداری',
        'warehouse' => 'انبارداری',
        'accounting' => 'حسابداری',
        'other' => 'سایر',
    ];

    // ----------------------------------------------------------------- ۶. تکنسین میدانی

    public const YES_NO = ['yes' => 'بله', 'no' => 'خیر'];

    public const VEHICLE_TYPES = [
        'motorcycle' => 'موتورسیکلت',
        'car' => 'خودروی سواری',
        'pickup' => 'وانت',
        'other' => 'سایر',
        'none' => 'بدون وسیله نقلیه',
    ];

    public const LICENSE_TYPES = [
        'motorcycle' => 'موتورسیکلت',
        'grade3' => 'پایه سوم',
        'grade2' => 'پایه دوم',
        'grade1' => 'پایه یک',
        'other' => 'سایر',
    ];

    public const MISSION_RANGE = [
        'city' => 'در محدوده شهر',
        'province' => 'در سطح استان',
        'other_cities' => 'شهرهای دیگر با هماهنگی',
        'none' => 'امکان مأموریت ندارم',
    ];

    public const CARRY_EQUIPMENT = ['yes' => 'بله', 'no' => 'خیر', 'limited' => 'با محدودیت'];

    // ----------------------------------------------------------------- ۷. شرایط همکاری

    public const START_AVAILABILITY = [
        'immediately' => 'بلافاصله',
        'lt_week' => 'کمتر از یک هفته',
        '1_2_weeks' => 'یک تا دو هفته',
        'lt_month' => 'کمتر از یک ماه',
        'gt_month' => 'بیشتر از یک ماه',
    ];

    public const SALARY_TYPES = [
        'negotiable' => 'طبق توافق',
        'company_offer' => 'مطابق پیشنهاد شرکت',
        'fixed' => 'مبلغ مشخص',
    ];

    public const YES_NO_COORDINATION = ['yes' => 'بله', 'no' => 'خیر', 'coordination' => 'با هماهنگی'];

    // ----------------------------------------------------------------- وضعیت پرونده‌ی متقاضی

    public const STATUSES = [
        'new' => 'درخواست جدید',
        'resume_review' => 'در انتظار بررسی رزومه',
        'initial_approval' => 'تأیید اولیه',
        'awaiting_contact' => 'در انتظار تماس',
        'invited' => 'دعوت به مصاحبه',
        'interviewed' => 'مصاحبه انجام شده',
        'awaiting_test' => 'در انتظار آزمون تخصصی',
        'awaiting_decision' => 'در انتظار تصمیم نهایی',
        'offer_sent' => 'پیشنهاد همکاری ارسال شده',
        'accepted' => 'پذیرفته شده',
        'rejected' => 'رد شده',
        'withdrawn' => 'انصراف متقاضی',
        'resume_bank' => 'ذخیره در بانک رزومه',
        'hired' => 'تبدیل به کارمند',
    ];

    /** آنچه متقاضی در «استعلام کد پیگیری» می‌بیند (جزئیات داخلی گزینش نمایش داده نمی‌شود). */
    public const PUBLIC_STATUSES = [
        'new' => 'درخواست شما ثبت شده و در صف بررسی است.',
        'resume_review' => 'درخواست شما در حال بررسی است.',
        'initial_approval' => 'درخواست شما در حال بررسی است.',
        'awaiting_contact' => 'درخواست شما بررسی شد؛ کارشناسان لوپ با شما تماس خواهند گرفت.',
        'invited' => 'برای مصاحبه دعوت شده‌اید؛ زمان مصاحبه با شما هماهنگ می‌شود.',
        'interviewed' => 'مصاحبه انجام شده و نتیجه در حال بررسی است.',
        'awaiting_test' => 'در انتظار آزمون تخصصی.',
        'awaiting_decision' => 'نتیجه‌ی نهایی در حال بررسی است.',
        'offer_sent' => 'پیشنهاد همکاری برای شما ارسال شده است.',
        'accepted' => 'درخواست همکاری شما پذیرفته شد.',
        'rejected' => 'متأسفانه در حال حاضر امکان همکاری فراهم نیست. از توجه شما سپاسگزاریم.',
        'withdrawn' => 'درخواست به انتخاب شما بسته شده است.',
        'resume_bank' => 'اطلاعات شما در بانک رزومه‌ی لوپ ذخیره شد و در فرصت‌های آینده بررسی می‌شود.',
        'hired' => 'به خانواده‌ی لوپ خوش آمدید.',
    ];

    public const SOURCES = [
        'site' => 'سایت',
        'in_person' => 'مراجعه حضوری',
        'staff_referral' => 'معرفی کارکنان',
        'phone' => 'تماس تلفنی',
        'other' => 'سایر',
    ];

    // ----------------------------------------------------------------- فرم گزینش و مصاحبه

    public const INSURANCE_REVIEW = ['approved' => 'تأیید', 'needs_review' => 'نیاز به بررسی بیشتر', 'mismatch' => 'عدم تطابق'];

    public const EXPERIENCE_REVIEW = ['suitable' => 'مناسب', 'reviewable' => 'قابل بررسی', 'unrelated' => 'نامرتبط'];

    public const SKILLS_REVIEW = ['fit' => 'متناسب با موقعیت', 'needs_test' => 'نیازمند آزمون', 'unfit' => 'نامتناسب'];

    public const RESUME_REVIEW_RESULT = [
        'initial_approval' => 'تأیید اولیه',
        'more_info' => 'نیاز به اطلاعات بیشتر',
        'rejected' => 'رد درخواست',
        'resume_bank' => 'ذخیره در بانک رزومه',
    ];

    public const CONTACT_RESULT = [
        'answered' => 'پاسخ داد',
        'no_answer' => 'پاسخ نداد',
        'call_again' => 'تماس مجدد لازم است',
        'withdrawn' => 'متقاضی انصراف داد',
        'ready' => 'آماده مصاحبه است',
    ];

    public const CONTACT_METHOD = ['phone' => 'تماس تلفنی', 'sms' => 'پیامک', 'messenger' => 'پیام‌رسان', 'other' => 'سایر'];

    public const INTERVIEW_PLACE = ['office' => 'دفتر شرکت', 'online' => 'آنلاین', 'phone' => 'تلفنی', 'other' => 'سایر'];

    public const INVITATION_STATUS = [
        'invited' => 'دعوت شد',
        'confirmed' => 'تأیید حضور',
        'not_confirmed' => 'عدم تأیید حضور',
        'absent' => 'غیبت در مصاحبه',
        'reschedule' => 'درخواست تغییر زمان',
    ];

    public const INTERVIEW_STAGE = ['first' => 'مصاحبه اول', 'second' => 'مصاحبه دوم', 'final' => 'مصاحبه نهایی'];

    public const INTERVIEW_TYPE = [
        'general' => 'عمومی',
        'technical' => 'تخصصی',
        'hr' => 'منابع انسانی',
        'unit_manager' => 'مدیر واحد',
        'ceo' => 'مدیرعامل',
    ];

    public const EVALUATION_LEVELS = ['weak' => 'ضعیف', 'average' => 'متوسط', 'good' => 'خوب', 'very_good' => 'بسیار خوب'];

    public const EVALUATION_CRITERIA = [
        'communication' => 'مهارت ارتباطی',
        'responsibility' => 'مسئولیت‌پذیری',
        'discipline' => 'نظم و وقت‌شناسی',
        'problem_solving' => 'توانایی حل مسئله',
        'motivation' => 'انگیزه همکاری',
        'teamwork' => 'کار تیمی',
        'job_fit' => 'تناسب با موقعیت شغلی',
    ];

    public const TEST_TYPES = [
        'practical' => 'آزمون عملی',
        'written' => 'آزمون کتبی',
        'computer' => 'آزمون کامپیوتری',
        'technical' => 'آزمون فنی',
        'office' => 'آزمون مهارت اداری',
        'other' => 'سایر',
    ];

    public const TEST_RESULT = ['passed' => 'قبول', 'retest' => 'نیاز به آزمون مجدد', 'failed' => 'مردود', 'not_done' => 'انجام نشد'];

    public const TECHNICIAN_TEST_ITEMS = [
        'hw_troubleshooting' => 'عیب‌یابی سخت‌افزار',
        'sw_install_troubleshooting' => 'نصب و عیب‌یابی نرم‌افزار',
        'parts_knowledge' => 'شناخت قطعات',
        'repair_skill' => 'مهارت تعمیر',
        'speed_accuracy' => 'سرعت و دقت انجام کار',
        'safety' => 'رعایت نکات ایمنی و نگهداری تجهیزات',
    ];

    public const FIELD_TECHNICIAN_TEST_ITEMS = [
        'onsite_fix' => 'توانایی تشخیص و رفع مشکل در محل',
        'org_customer' => 'مهارت ارتباط با مشتری سازمانی',
        'mission_report' => 'توانایی تهیه گزارش مأموریت',
        'timing' => 'رعایت زمان‌بندی',
        'equipment' => 'توانایی مدیریت تجهیزات و قطعات',
    ];

    public const OFFICE_TEST_ITEMS = [
        'office_suite' => 'مهارت کار با Office',
        'data_entry' => 'ورود و مدیریت اطلاعات',
        'automation_crm' => 'توانایی کار با اتوماسیون و CRM',
        'accuracy' => 'دقت در امور اداری',
        'follow_up' => 'مهارت پاسخ‌گویی و پیگیری',
    ];

    public const INTERVIEW_DECISION = [
        'approved' => 'تأیید',
        'conditional' => 'تأیید مشروط',
        'reinterview' => 'مصاحبه مجدد',
        'rejected' => 'رد درخواست',
        'transfer' => 'انتقال به موقعیت شغلی دیگر',
    ];

    public const NOTIFY_METHOD = ['phone' => 'تماس تلفنی', 'sms' => 'پیامک', 'email' => 'ایمیل', 'in_person' => 'حضوری'];

    public const OFFER_RESPONSE = [
        'accepted' => 'پذیرفته',
        'reviewing' => 'در حال بررسی',
        'negotiation' => 'درخواست مذاکره',
        'declined' => 'رد پیشنهاد',
        'no_response' => 'بدون پاسخ',
    ];

    public const HIRING_FILE_STATUS = [
        'awaiting_documents' => 'در انتظار مدارک',
        'documents_complete' => 'مدارک تکمیل شده',
        'contract_ready' => 'قرارداد آماده امضا',
        'contract_signed' => 'قرارداد امضا شده',
        'file_complete' => 'پرونده تکمیل شده',
    ];

    public const FINAL_RESULT = ['hire' => 'تأیید استخدام', 'reject' => 'رد درخواست', 'resume_bank' => 'نگهداری در بانک رزومه'];

    // ----------------------------------------------------------------- پرونده‌ی استخدامی

    public const PERSON_TYPES = ['staff' => 'پرسنل', 'technician' => 'تکنسین', 'manager' => 'مدیر'];

    public const WORK_LOCATIONS = [
        'head_office' => 'دفتر مرکزی',
        'branch' => 'شعبه',
        'workshop' => 'کارگاه فنی',
        'field' => 'مأموریت میدانی',
        'other' => 'سایر',
    ];

    public const EMPLOYMENT_COOPERATION_TYPES = [
        'full_time' => 'تمام‌وقت',
        'part_time' => 'پاره‌وقت',
        'contract' => 'قراردادی',
        'project' => 'پروژه‌ای',
    ];

    public const CONTRACT_TYPES = [
        'fixed_term' => 'مدت‌دار',
        'indefinite' => 'نامحدود در صورت توافق',
        'project' => 'پروژه‌ای',
        'other' => 'سایر',
    ];

    public const EMPLOYMENT_STATUS = [
        'active' => 'فعال',
        'awaiting_start' => 'در انتظار شروع',
        'suspended' => 'تعلیق',
        'terminated' => 'خاتمه همکاری',
    ];

    public const TECHNICIAN_KIND = ['field' => 'میدانی', 'internal' => 'اداری (داخلی)'];

    public const MAIN_SPECIALTIES = [
        'laptop' => 'لپ‌تاپ',
        'pc_aio' => 'کامپیوتر و AIO',
        'printer' => 'پرینتر',
        'copier' => 'دستگاه کپی صنعتی',
        'monitor' => 'مانیتور',
        'network' => 'شبکه',
        'hard_drive' => 'هارد',
        'all_in_one' => 'آل این وان',
        'other' => 'سایر',
    ];

    public const SKILL_TIERS = ['basic' => 'مقدماتی', 'intermediate' => 'متوسط', 'advanced' => 'پیشرفته'];

    public const VEHICLES_SIMPLE = [
        'motorcycle' => 'موتورسیکلت',
        'car' => 'خودروی سواری',
        'pickup' => 'وانت',
        'other' => 'سایر',
        'none' => 'ندارد',
    ];

    public const SALARY_PAYMENT_TYPES = [
        'monthly' => 'ماهانه',
        'agreement' => 'طبق توافق نامه',
        'percent' => 'درصد',
        'biweekly' => 'تسویه ۱۵ روزه',
        'other' => 'سایر',
    ];

    public const EMPLOYMENT_DOCUMENTS = [
        'national_card' => 'تصویر کارت ملی',
        'birth_certificate' => 'تصویر شناسنامه',
        'photo' => 'عکس پرسنلی',
        'education' => 'مدارک تحصیلی',
        'technical' => 'مدارک فنی و تخصصی',
        'resume' => 'رزومه',
        'driving_license' => 'گواهینامه رانندگی',
        'signed_contract' => 'قرارداد همکاری امضاشده',
        'nda' => 'فرم تعهدات و محرمانگی',
        'bank_info' => 'اطلاعات حساب بانکی',
        'insurance' => 'بیمه تامین اجتماعی',
        'other' => 'سایر مدارک موردنیاز',
    ];

    public const DOCUMENTS_STATUS = [
        'complete' => 'تکمیل‌شده',
        'incomplete' => 'ناقص',
        'awaiting' => 'در انتظار دریافت',
        'needs_review' => 'نیازمند بررسی',
    ];

    public const EQUIPMENT = [
        'laptop' => 'لپ‌تاپ',
        'computer' => 'کامپیوتر',
        'mobile' => 'موبایل سازمانی',
        'sim' => 'سیم‌کارت',
        'tools' => 'ابزار فنی',
        'access_card' => 'کارت ورود',
        'other' => 'سایر',
    ];

    public const DELIVERY_STATUS = ['delivered' => 'تحویل شده', 'pending' => 'در انتظار تحویل'];

    public const ONBOARDING_STEPS = [
        'unit_intro' => 'معرفی به واحد مربوطه',
        'manager_intro' => 'معرفی مدیر مستقیم',
        'rules' => 'آموزش قوانین داخلی',
        'automation' => 'آموزش اتوماسیون LOOP',
        'duties' => 'آموزش شرح وظایف',
        'security' => 'آموزش ایمنی و امنیت اطلاعات',
        'equipment' => 'تحویل تجهیزات و ابزار',
        'schedule' => 'تعیین برنامه کاری',
        'goals' => 'تعیین اهداف اولیه',
        'evaluation' => 'ثبت برنامه ارزیابی اولیه',
    ];

    public const TRAINING_STATUS = ['done' => 'تکمیل شده', 'in_progress' => 'در حال انجام', 'not_started' => 'شروع نشده'];

    public const FILE_STATUS = [
        'draft' => 'پیش‌نویس',
        'awaiting_documents' => 'در انتظار مدارک',
        'awaiting_approval' => 'در انتظار تأیید',
        'completed' => 'تکمیل‌شده',
    ];

    /** @return array<string, string> همه‌ی مهارت‌ها (کلید ← عنوان) */
    public static function allSkills(): array
    {
        return array_merge(...array_map(fn ($g) => $g['skills'], array_values(self::SKILL_GROUPS)));
    }
}
