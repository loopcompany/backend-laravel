<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnicianRegistrationRequest;
use App\Http\Requests\TechnicianPhoneVerificationRequest;
use App\Http\Requests\TechnicianLoginRequest;
use App\Http\Requests\TechnicianForgotPasswordRequest;
use App\Services\TechnicianRegistrationService;
use App\Services\LoginActivityService;
use Illuminate\Http\Request;

class TechnicianRegistrationController extends Controller
{
    public function register(TechnicianRegistrationRequest $request, TechnicianRegistrationService $service)
    {


        $data = $request->validated();
        \Log::info('Technician Registration Data:', $data);
        // اضافه کردن فایل رزومه به داده‌ها اگر وجود داشته باشد
        if ($request->hasFile('resume')) {
            $data['resume'] = $request->file('resume');

        } else {
        }

        $result = $service->register($data);

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'INVALID_REFERRAL_CODE' => 422,
                'PHONE_ALREADY_VERIFIED' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'REGISTRATION_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ], 201);
    }

    public function verifyPhone(TechnicianPhoneVerificationRequest $request, TechnicianRegistrationService $service)
    {
        $result = $service->verifyPhone($request->phone, $request->code);

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'TECHNICIAN_NOT_FOUND' => 404,
                'NO_VERIFICATION_CODE' => 400,
                'INVALID_VERIFICATION_CODE' => 422,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'VERIFICATION_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }

    public function resendVerificationCode(Request $request, TechnicianRegistrationService $service)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
        ], [
            'phone.required' => 'شماره تلفن الزامی است.',
            'phone.regex' => 'شماره تلفن باید با 09 شروع شده و 11 رقم باشد.',
        ]);

        $result = $service->resendVerificationCode($request->phone);

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'TECHNICIAN_NOT_FOUND' => 404,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'RESEND_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }

    public function validateReferralCode(Request $request, TechnicianRegistrationService $service)
    {
        $request->validate([
            'referral_code' => 'required|string|max:50',
        ], [
            'referral_code.required' => 'کد پرسنلی الزامی است.',
            'referral_code.max' => 'کد پرسنلی نباید بیش از 50 کاراکتر باشد.',
        ]);

        $isValid = $service->validateReferralCode($request->referral_code);

        return response()->json([
            'success' => true,
            'data' => [
                'is_valid' => $isValid,
                'message' => $isValid ? 'کد پرسنلی معتبر است.' : 'کد پرسنلی نامعتبر است.'
            ]
        ]);
    }

    public function login(TechnicianLoginRequest $request, TechnicianRegistrationService $service, LoginActivityService $loginActivityService)
    {
        $result = $service->login($request->referral_code, $request->password);

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'TECHNICIAN_NOT_FOUND' => 404,
                'PHONE_NOT_VERIFIED' => 403,
                'INVALID_PASSWORD' => 401,
                'ACCOUNT_DISABLED' => 403,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'LOGIN_ERROR'
            ], $statusCode);
        }

        // Log login activity (non-blocking)
        try {
            $loginActivityService->logLogin('technician', $result['data']['technician']['id'], $request);
        } catch (\Exception $e) {
            // Silently fail - don't disrupt login
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }

    public function logout(Request $request, LoginActivityService $loginActivityService)
    {
        try {
            $technician = $request->user();

            if (!$technician) {
                return response()->json([
                    'success' => false,
                    'message' => '????? ????? ???? ???? ???.',
                    'error_code' => 'UNAUTHORIZED'
                ], 401);
            }

            $technicianId = $technician->id;

            // ??? ???? ????
            $request->user()->currentAccessToken()->delete();

            // Log logout activity (non-blocking)
            try {
                $loginActivityService->logLogout('technician', $technicianId, $request, 'logout');
            } catch (\Exception $e) {
                // Silently fail - don't disrupt logout
            }

            return response()->json([
                'success' => true,
                'message' => '???? ?? ?????? ????? ??.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '??? ?? ????. ???? ????? ???? ????.',
                'error_code' => 'LOGOUT_ERROR'
            ], 500);
        }
    }

    public function logoutFromAllDevices(Request $request, LoginActivityService $loginActivityService)
    {
        try {
            $technician = $request->user();

            if (!$technician) {
                return response()->json([
                    'success' => false,
                    'message' => '????? ????? ???? ???? ???.',
                    'error_code' => 'UNAUTHORIZED'
                ], 401);
            }

            $technicianId = $technician->id;

            // ??? ???? ??????? ?????
            $technician->tokens()->delete();

            // Log logout_all activity (non-blocking)
            try {
                $loginActivityService->logLogout('technician', $technicianId, $request, 'logout_all');
            } catch (\Exception $e) {
                // Silently fail - don't disrupt logout
            }

            return response()->json([
                'success' => true,
                'message' => '???? ?? ??? ???????? ?? ?????? ????? ??.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '??? ?? ???? ?? ??? ????????. ???? ????? ???? ????.',
                'error_code' => 'LOGOUT_ALL_ERROR'
            ], 500);
        }
    }

    public function validateToken(Request $request)
    {
        try {
            $technician = $request->user();

            if (!$technician) {
                return response()->json([
                    'success' => false,
                    'message' => 'توکن نامعتبر یا منقضی شده است.',
                    'valid' => false,
                    'error_code' => 'INVALID_TOKEN'
                ], 401);
            }
            $technician->loadAvg('reviews as average_rating', 'technician_rate');

            // بررسی دسترسی تکنسین
            if (!$technician->has_access) {
                return response()->json([
                    'success' => false,
                    'message' => 'دسترسی تکنسین مسدود شده است.',
                    'valid' => false,
                    'error_code' => 'ACCESS_DENIED'
                ], 403);
            }

            // بررسی تأیید شماره تلفن
            if (!$technician->phone_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'شماره موبایل تکنسین تایید نشده است.',
                    'valid' => false,
                    'requires_verification' => true,
                    'error_code' => 'PHONE_NOT_VERIFIED'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'توکن معتبر است.',
                'valid' => true,
                'data' => [
                    'technician' => [
                        // اطلاعات شخصی پایه
                        'id' => $technician->id,
                        'name' => $technician->name,
                        'phone' => $technician->phone,
                        'email' => $technician->email,
                        'melicode' => $technician->melicode,
                        'birth_date' => $technician->birth_date,
                        'father_name' => $technician->father_name,
                        'issued_from' => $technician->issued_from,
                        'serial_number' => $technician->serial_number,
                        'marital_status' => $technician->marital_status,
                        'military_status' => $technician->military_status,
                        'education_status' => $technician->education_status,
                        'education_field' => $technician->education_status,
                        'telephone' => $technician->telephone,
                        'mobile' => $technician->mobile,
                        'wallet' => $technician->wallet,
                        'total_settlements' => $technician->settlements()->sum('amount') ?? 0,

                        // آدرس و موقعیت
                        'home_postal_code' => $technician->home_postal_code,
                        'region' => $technician->region,
                        'city' => $technician->city,
                        'home_address' => $technician->home_address,

                        // اطلاعات احراز هویت
                        'phone_verified_at' => $technician->phone_verified_at?->toISOString(),
                        'email_verified_at' => $technician->email_verified_at?->toISOString(),
                        'profile_photo_path' => $technician->profile_photo_path ? url('storage/' . $technician->profile_photo_path) : null,

                        // کدهای پرسنلی
                        'referral_code' => $technician->referral_code,
                        'other_referral_code' => $technician->other_referral_code,
                        'has_access' => (bool) $technician->has_access,

                        // مهارت‌ها و توانایی‌ها
                        'idea' => $technician->idea,
                        'software_skill' => $technician->software_skill,
                        'hardware_skill' => $technician->hardware_skill,
                        'software_weakness' => $technician->software_weakness,
                        'hardware_weakness' => $technician->hardware_weakness,
                        'resume' => $technician->resume ? url('storage/' . $technician->resume) : null,
                        'technician_type' => $technician->technician_type,

                        // گواهینامه
                        'certificate_number' => $technician->certificate_number,
                        'certificate_issue_date' => $technician->certificate_issue_date,
                        'licence_date' => $technician->licence_date,
                        'vehicle_type' => $technician->vehicle_type,

                        // اطلاعات خودرو
                        'car_model' => $technician->car_model,
                        'car_color' => $technician->car_color,
                        'car_plate' => $technician->car_plate,
                        'car_year' => $technician->car_year,
                        'car_fuel_type' => $technician->car_fuel_type,
                        'car_vin' => $technician->car_vin,
                        'car_insurance_code' => $technician->car_insurance_code,
                        'car_insurance_expiry_date' => $technician->car_insurance_expiry_date,

                        // اطلاعات بانکی
                        'bank_shaba_number' => $technician->bank_shaba_number,
                        'bank_name' => $technician->bank_name,
                        'bank_card_number' => $technician->bank_card_number,

                        'at_work' => $technician->at_work,
                        'average_rating' => $technician->average_rating,
                        'apple_check' => 0, // وضعیت بررسی اپل
                        // تاریخ‌ها
                        'created_at' => $technician->created_at?->toISOString(),
                        'updated_at' => $technician->updated_at?->toISOString(),
                    ],
                    'token_info' => [
                        'current_token_id' => $request->user()->currentAccessToken()->id ?? null,
                        'validated_at' => now()->toISOString(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی توکن.',
                'valid' => false,
                'error_code' => 'VALIDATION_ERROR'
            ], 500);
        }
    }
}

