<?php

namespace App\Services;

use App\Repositories\TechnicianRepository;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class TechnicianRegistrationService
{
    public function __construct(private TechnicianRepository $repo)
    {
    }

    public function register(array $data): array
    {
        // اگر کد پرسنلی دیگران وارد شده، اعتبار آن را چک کن
        if (!empty($data['other_referral_code'])) {
            $referrer = $this->repo->findByReferralCode($data['other_referral_code']);
            if (!$referrer) {
                return [
                    'success' => false,
                    'message' => 'کد پرسنلی وارد شده معتبر نیست.',
                    'error_code' => 'INVALID_REFERRAL_CODE'
                ];
            }
        }
        Log::info($data['phone']);
        // چک کردن وجود تکنسین با همین شماره
        $existingTechnician = $this->repo->findByPhone($data['phone']);

        if ($existingTechnician) {
            if ($existingTechnician->phone_verified_at) {
                return [
                    'success' => false,
                    'message' => 'این شماره تلفن قبلاً ثبت نام و تأیید شده است.',
                    'error_code' => 'PHONE_ALREADY_VERIFIED'
                ];
            }

            // اگر تأیید نشده، اطلاعات را آپدیت کن
            $technician = $this->updateTechnicianData($existingTechnician, $data);
        } else {
            // ایجاد تکنسین جدید
            $technician = $this->createNewTechnician($data);
        }

        // ارسال کد تأیید
        $verificationResult = $this->sendVerificationCode($technician, $data['hashApp']);

        if (!$verificationResult['success']) {
            return $verificationResult;
        }

        return [
            'success' => true,
            'message' => 'ثبت نام با موفقیت انجام شد. کد تأیید به شماره شما ارسال شد.',
            'data' => [
                'technician_id' => $technician->id,
                'phone' => $technician->phone,
                'verification_sent' => true
            ]
        ];
    }

    public function verifyPhone(string $phone, string $code): array
    {
        $technician = $this->repo->findUnverifiedByPhone($phone);

        if (!$technician) {
            return [
                'success' => false,
                'message' => 'تکنسینی با این شماره یافت نشد یا قبلاً تأیید شده است.',
                'error_code' => 'TECHNICIAN_NOT_FOUND'
            ];
        }

        if (!$technician->phone_verify_code) {
            return [
                'success' => false,
                'message' => 'کد تأیید برای این شماره ارسال نشده است.',
                'error_code' => 'NO_VERIFICATION_CODE'
            ];
        }

        if (!Hash::check($code, $technician->phone_verify_code)) {
            return [
                'success' => false,
                'message' => 'کد تأیید نادرست است.',
                'error_code' => 'INVALID_VERIFICATION_CODE'
            ];
        }

        // تأیید شماره تلفن
        $this->repo->verifyPhone($technician);

        // ایجاد رمز عبور و ارسال پیامک
        $password = $this->generateAndSendPassword($technician);

        return [
            'success' => true,
            'message' => 'شماره تلفن با موفقیت تأیید شد. رمز عبور به شماره شما ارسال شد.',
            'data' => [
                'technician_id' => $technician->id,
                'phone' => $technician->phone,
                'verified_at' => $technician->phone_verified_at,
                'password_sent' => true
            ]
        ];
    }

    public function resendVerificationCode(string $phone): array
    {
        $technician = $this->repo->findUnverifiedByPhone($phone);

        if (!$technician) {
            return [
                'success' => false,
                'message' => 'تکنسینی با این شماره یافت نشد یا قبلاً تأیید شده است.',
                'error_code' => 'TECHNICIAN_NOT_FOUND'
            ];
        }

        $verificationResult = $this->sendVerificationCode($technician);

        if (!$verificationResult['success']) {
            return $verificationResult;
        }

        return [
            'success' => true,
            'message' => 'کد تأیید مجدداً ارسال شد.',
            'data' => [
                'phone' => $technician->phone,
                'verification_sent' => true
            ]
        ];
    }

    private function createNewTechnician(array $data): object
    {
        // تولید کد پرسنلی یکتا

        // جدا کردن expertise_ids از داده‌های اصلی
        $expertiseIds = $data['expertise_ids'] ?? [];
        unset($data['expertise_ids']);

        // مدیریت آپلود فایل رزومه
        if (isset($data['resume']) && $data['resume'] instanceof UploadedFile) {
            $resumeFile = $data['resume'];
            $resumePath = $this->uploadResumeFile($resumeFile);
            $data['resume'] = $resumePath;
        } else {
            \Log::warning('No resume file found or invalid type', [
                'isset' => isset($data['resume']),
                'type' => isset($data['resume']) ? gettype($data['resume']) : 'not set'
            ]);
            // اگر فایل ارسال نشده، مقدار null قرار می‌دهیم
            $data['resume'] = null;
        }

        // ایجاد تکنسین
        $technician = $this->repo->create($data);
        $data['referral_code'] = $this->repo->createUniquCodeForTechnician(21, $data['region'], $technician, 2000);
        $update_referral_code = $this->repo->update($technician, $data);
        // اتصال تخصص‌ها اگر وجود داشته باشند
        if (!empty($expertiseIds)) {
            $this->repo->attachExpertises($technician, $expertiseIds);
        }

        return $technician;
    }

    private function updateTechnicianData(object $technician, array $data): object
    {
        // حذف فیلدهایی که نباید آپدیت شوند
        unset($data['phone']); // شماره تلفن تغییر نمی‌کند

        // جدا کردن expertise_ids از داده‌های اصلی
        $expertiseIds = $data['expertise_ids'] ?? [];
        unset($data['expertise_ids']);

        // مدیریت آپلود فایل رزومه
        if (isset($data['resume']) && $data['resume'] instanceof UploadedFile) {
            \Log::info('Resume file detected in update, starting upload...');
            $resumeFile = $data['resume'];
            $resumePath = $this->uploadResumeFile($resumeFile);
            $data['resume'] = $resumePath;
            \Log::info('Resume uploaded successfully in update', ['path' => $resumePath]);
        } else {
            \Log::warning('No resume file found in update or invalid type', [
                'isset' => isset($data['resume']),
                'type' => isset($data['resume']) ? gettype($data['resume']) : 'not set'
            ]);
            // اگر فایل ارسال نشده، این فیلد را حذف می‌کنیم تا مقدار قبلی حفظ شود
            unset($data['resume']);
        }

        // آپدیت اطلاعات تکنسین
        $technician = $this->repo->update($technician, $data);

        // اتصال تخصص‌ها اگر وجود داشته باشند
        if (!empty($expertiseIds)) {
            $this->repo->syncExpertises($technician, $expertiseIds);
        }

        return $technician;
    }

    private function sendVerificationCode(object $technician, ?string $hashApp = ''): array
    {
        try {
            // تولید کد 6 رقمی
            $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);

            // هش کردن و ذخیره کد
            $hashedCode = Hash::make($code);
            $this->repo->setVerificationCode($technician, $hashedCode);

            // ارسال SMS با template 695686 و متغیر CODE
            $smsResult = Helper::send_sms(
                $technician->phone,
                '695686',
                ['CODE', 'HASHAPP'],
                [$code, $hashApp]
            );

            if ($smsResult != true) {
                return [
                    'success' => false,
                    'message' => 'خطا در ارسال پیامک. لطفاً مجدداً تلاش کنید.',
                    'error_code' => 'SMS_SEND_FAILED'
                ];
            }

            return [
                'success' => true,
                'code_sent' => true
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در ارسال کد تأیید.',
                'error_code' => 'VERIFICATION_CODE_ERROR'
            ];
        }
    }

    private function generateAndSendPassword(object $technician): string
    {
        try {
            // تولید رمز عبور 8 کاراکتری
            $password = Str::random(8);

            // هش کردن و ذخیره رمز عبور
            $hashedPassword = Hash::make($password);
            $this->repo->update($technician, ['password' => $hashedPassword]);

            // ارسال SMS با template 812565 و متغیرهای CODE و PASS
            // ترتیب: ابتدا کد پرسنلی (#CODE#) سپس رمز عبور (#PASS#)
            Helper::send_sms(
                $technician->phone,
                '812565',
                ['CODE', 'PASS'],
                [$technician->referral_code, $password]
            );

            return $password;

        } catch (\Exception $e) {
            // در صورت خطا، رمز عبور پیش‌فرض
            $defaultPassword = '12345678';
            $hashedPassword = Hash::make($defaultPassword);
            $this->repo->update($technician, ['password' => $hashedPassword]);

            return $defaultPassword;
        }
    }

    public function validateReferralCode(string $referralCode): bool
    {
        return $this->repo->findByReferralCode($referralCode) != null;
    }

    public function login(string $referralCode, string $password): array
    {
        // پیدا کردن تکنسین بر اساس کد پرسنلی
        $technician = $this->repo->findByReferralCode($referralCode);

        if (!$technician) {
            return [
                'success' => false,
                'message' => 'تکنسینی با این کد پرسنلی یافت نشد.',
                'error_code' => 'TECHNICIAN_NOT_FOUND'
            ];
        }

        // بررسی تأیید شماره تلفن
        if (!$technician->phone_verified_at) {
            return [
                'success' => false,
                'message' => 'شماره تلفن شما هنوز تأیید نشده است.',
                'error_code' => 'PHONE_NOT_VERIFIED'
            ];
        }

        // بررسی رمز عبور
        if (!Hash::check($password, $technician->password)) {
            return [
                'success' => false,
                'message' => 'رمز عبور نادرست است.',
                'error_code' => 'INVALID_PASSWORD'
            ];
        }

        // بررسی وضعیت دسترسی
        if (!$technician->has_access) {
            return [
                'success' => false,
                'message' => $technician->limit_access_reason,
                'error_code' => 'ACCOUNT_DISABLED'
            ];
        }
        if ($technician->approval_status !== 'approved') {
            return [
                'success' => false,
                'message' => 'حساب کاربری شما تأیید نشده است. تا زمان تأیید نمی‌توانید وارد شوید.',
                'error_code' => 'ACCOUNT_NOT_APPROVED'
            ];
        }

        // تولید توکن Sanctum
        $token = $technician->createToken('technician-app')->plainTextToken;

        return [
            'success' => true,
            'message' => 'ورود موفقیت‌آمیز بود.',
            'data' => [
                'technician' => [
                    'id' => $technician->id,
                    'name' => $technician->name,
                    'phone' => $technician->phone,
                    'referral_code' => $technician->referral_code,
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ];
    }

    public function sendResetCode(array $data): array
    {
        // پیدا کردن تکنسین بر اساس کد پرسنلی
        $technician = $this->repo->findByReferralCode($data['referral_code']);

        if (!$technician) {
            return [
                'success' => false,
                'message' => 'اطلاعات وارد شده صحیح نیست.',
                'error_code' => 'INVALID_DATA'
            ];
        }

        // بررسی تأیید شماره تلفن (ثبت نام کامل شده باشد)
        if (!$technician->phone_verified_at) {
            return [
                'success' => false,
                'message' => 'ثبت نام شما کامل نشده است. لطفاً ابتدا فرآیند ثبت نام را تکمیل کنید.',
                'error_code' => 'REGISTRATION_NOT_COMPLETE'
            ];
        }

        // بررسی تطابق اطلاعات
        if (
            $technician->phone != $data['phone'] ||
            $technician->melicode != $data['melicode'] ||
            $technician->email != $data['email']
        ) {
            return [
                'success' => false,
                'message' => 'اطلاعات وارد شده با اطلاعات ثبت شده مطابقت ندارد.',
                'error_code' => 'DATA_MISMATCH'
            ];
        }

        // تولید کد 6 رقمی
        $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);

        // هش کردن و ذخیره کد
        $hashedCode = Hash::make($code);
        $this->repo->setVerificationCode($technician, $hashedCode);

        // ارسال SMS با template 695686 و متغیر CODE
        $smsResult = Helper::send_sms(
            $technician->phone,
            '695686',
            ['CODE', 'HASHAPP'],
            [$code, $data['hashApp']]
        );

        if ($smsResult != true) {
            return [
                'success' => false,
                'message' => 'خطا در ارسال پیامک. لطفاً مجدداً تلاش کنید.',
                'error_code' => 'SMS_SEND_ERROR'
            ];
        }

        return [
            'success' => true,
            'message' => 'کد بازیابی رمز عبور به شماره شما ارسال شد.',
            'phone' => $technician->phone
        ];
    }

    public function verifyResetCode(string $phone, string $code): array
    {
        $technician = $this->repo->findByPhone($phone);

        if (!$technician) {
            return [
                'success' => false,
                'message' => 'تکنسینی با این شماره یافت نشد.',
                'error_code' => 'TECHNICIAN_NOT_FOUND'
            ];
        }

        if (!$technician->phone_verify_code) {
            return [
                'success' => false,
                'message' => 'کد بازیابی برای این شماره ارسال نشده است.',
                'error_code' => 'NO_RESET_CODE'
            ];
        }

        if (!Hash::check($code, $technician->phone_verify_code)) {
            return [
                'success' => false,
                'message' => 'کد بازیابی نادرست است.',
                'error_code' => 'INVALID_RESET_CODE'
            ];
        }

        return [
            'success' => true,
            'message' => 'کد بازیابی تأیید شد.',
            'data' => [
                'phone' => $technician->phone,
                'verified' => true
            ]
        ];
    }

    public function resetPassword(string $phone, string $newPassword): array
    {
        $technician = $this->repo->findByPhone($phone);

        if (!$technician) {
            return [
                'success' => false,
                'message' => 'تکنسینی با این شماره یافت نشد.',
                'error_code' => 'TECHNICIAN_NOT_FOUND'
            ];
        }

        // تغییر رمز عبور
        $this->repo->update($technician, [
            'password' => Hash::make($newPassword)
        ]);

        // حذف کد تأیید (بدون تغییر phone_verified_at)
        $this->repo->clearVerificationCode($technician);

        return [
            'success' => true,
            'message' => 'رمز عبور با موفقیت تغییر یافت.',
            'data' => [
                'phone' => $technician->phone
            ]
        ];
    }

    private function uploadResumeFile(UploadedFile $file): string
    {
        try {
            // تولید نام یکتا برای فایل
            $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();

            // ذخیره فایل در پوشه resumes
            $filePath = $file->storeAs('resumes', $fileName, 'public');

            return $filePath;

        } catch (\Exception $e) {
            // در صورت خطا، لاگ کرده و null برمی‌گردانیم
            \Log::error('Resume upload failed: ' . $e->getMessage());
            throw new \Exception('خطا در آپلود فایل رزومه.');
        }
    }
}