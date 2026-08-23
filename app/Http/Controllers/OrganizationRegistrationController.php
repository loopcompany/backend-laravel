<?php

namespace App\Http\Controllers;

use App\DTOs\OrganizationRegistrationDTO;
use App\Http\Requests\OrganizationRegistrationRequest;
use App\Services\OrganizationRegistrationService;
use App\Services\LoginActivityService;
use App\Services\PhoneVerificationService;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrganizationRegistrationController extends Controller
{
    public function __construct(
        protected OrganizationRegistrationService $registrationService,
        protected PhoneVerificationService $verificationService,
        protected UserRepository $userRepository
    ) {}

    /**
     * ثبت‌نام سازمان جدید
     * 
     * @param OrganizationRegistrationRequest $request
     * @return JsonResponse
     */
    public function register(OrganizationRegistrationRequest $request): JsonResponse
    {
        try {
            // تبدیل request به DTO
            $dto = OrganizationRegistrationDTO::fromArray($request->validated());

            // ثبت‌نام سازمان
            $result = $this->registrationService->register($dto);

            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null,
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => $result['data'],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Organization registration controller error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ثبت‌نام سازمان. لطفاً دوباره تلاش کنید.',
            ], 500);
        }
    }

    /**
     * تایید شماره موبایل سازمان
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyPhone(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
            'code' => 'required|string|size:6',
        ], [
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.regex' => 'فرمت شماره موبایل نامعتبر است.',
            'code.required' => 'کد تایید الزامی است.',
            'code.size' => 'کد تایید باید 6 رقم باشد.',
        ]);

        try {
            $result = $this->verificationService->verifyPhone(
                $request->input('phone'),
                $request->input('code'),
                false // not password reset
            );

            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                ], 400);
            }

            // Get verified user and their organization data
            $user = $result['user'];
            $organization = null;
            
            if ($user && $user->account_type === 'organization') {
                $organization = $this->userRepository->findById($user->id)?->organization;
            }

            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => [
                    'token' => $result['token'] ?? null,
                    'user' => $user,
                    'organization' => $organization ? [
                        'id' => $organization->id,
                        'organization_name' => $organization->organization_name,
                        'organization_code' => $organization->organization_code,
                        'manager_full_name' => $organization->manager_full_name,
                    ] : null,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Organization phone verification error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'خطا در تایید شماره موبایل.',
            ], 500);
        }
    }

    /**
     * ارسال مجدد کد تایید
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function resendVerificationCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
        ], [
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.regex' => 'فرمت شماره موبایل نامعتبر است.',
        ]);

        try {
            // Get SmsService from container
            $smsService = app(\App\Services\SmsService::class);
            
            $result = $this->verificationService->resendVerificationCode(
                $request->input('phone'),
                $smsService,
                false // not password reset
            );

            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Organization resend verification code error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ارسال مجدد کد تایید.',
            ], 500);
        }
    }

    /**
     * ورود سازمان با کد سازمانی
     * 
     * @param Request $request
     * @param LoginActivityService $loginActivityService
     * @return JsonResponse
     */
    public function login(Request $request, LoginActivityService $loginActivityService): JsonResponse
    {
        $request->validate([
            'organization_code' => 'required|string',
            'password' => 'required|string',
        ], [
            'organization_code.required' => 'کد سازمانی الزامی است.',
            'organization_code.size' => 'کد سازمانی باید 6 رقم باشد.',
            'password.required' => 'رمز عبور الزامی است.',
        ]);

        try {
            $result = $this->registrationService->login(
                $request->input('organization_code'),
                $request->input('password')
            );

            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null,
                ], 401);
            }

            // Log login activity (non-blocking)
            try {
                $loginActivityService->logLogin('organization', $result['data']['user']['id'], $request);
            } catch (\Exception $e) {
                // Silently fail - don't disrupt login
            }

            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => $result['data'],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Organization login error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ورود. لطفاً دوباره تلاش کنید.',
            ], 500);
        }
    }

    /**
     * خروج از حساب کاربری
     * 
     * @param Request $request
     * @param LoginActivityService $loginActivityService
     * @return JsonResponse
     */
    public function logout(Request $request, LoginActivityService $loginActivityService): JsonResponse
    {
        try {
            $user = $request->user();
            $userId = $user->id;

            $request->user()->currentAccessToken()->delete();

            // Log logout activity (non-blocking)
            try {
                $loginActivityService->logLogout('organization', $userId, $request, 'logout');
            } catch (\Exception $e) {
                // Silently fail - don't disrupt logout
            }

            return response()->json([
                'status' => 'success',
                'message' => __("Exit was successful."),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Organization logout error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'خطا در خروج از حساب کاربری.',
            ], 500);
        }
    }

    /**
     * خروج از تمام دستگاه‌ها
     * 
     * @param Request $request
     * @param LoginActivityService $loginActivityService
     * @return JsonResponse
     */
    public function logoutFromAllDevices(Request $request, LoginActivityService $loginActivityService): JsonResponse
    {
        try {
            $user = $request->user();
            $userId = $user->id;

            $request->user()->tokens()->delete();

            // Log logout_all activity (non-blocking)
            try {
                $loginActivityService->logLogout('organization', $userId, $request, 'logout_all');
            } catch (\Exception $e) {
                // Silently fail - don't disrupt logout
            }

            return response()->json([
                'status' => 'success',
                'message' => __("Exit from all devices was successful."),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Organization logout from all devices error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'خطا در خروج از حساب کاربری.',
            ], 500);
        }
    }

    /**
     * اعتبارسنجی توکن
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function validateToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return response()->json([
                'status' => 'success',
                'message' => 'توکن معتبر است.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'account_type' => $user->account_type,
                    ],
                    'organization' => $user->organization,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Organization token validation error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'توکن نامعتبر است.',
            ], 401);
        }
    }

    /**
     * ارسال کد بازیابی رمز عبور برای سازمان
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'organization_code' => 'required|string',
        ], [
            'organization_code.required' => 'کد سازمانی الزامی است.',
        ]);

        try {
            $smsService = app(\App\Services\SmsService::class);
            $organizationRepository = app(\App\Repositories\OrganizationRepository::class);

            // پیدا کردن سازمان با کد سازمانی
            $organization = $organizationRepository->findByOrganizationCode($request->input('organization_code'));

            if (!$organization) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'کد سازمانی یافت نشد.',
                ], 404);
            }

            $user = $organization->user;

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'کاربر مرتبط با این سازمان یافت نشد.',
                ], 404);
            }

            // تولید کد تایید
            $verificationCode = $smsService->generateVerificationCode();
            $hashedCode = $smsService->hashVerificationCode($verificationCode);

            // ذخیره کد تایید
            $this->userRepository->updateVerificationCode($user->phone, $hashedCode);

            // ارسال SMS
            $smsSent = $smsService->sendVerificationCode($user->phone, $verificationCode, $request->input('hashApp'));

            if (!$smsSent) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'خطا در ارسال پیامک.',
                ], 500);
            }

            Log::info('Organization password reset code sent', [
                'organization_id' => $organization->id,
                'organization_code' => $organization->organization_code,
                'phone' => $user->phone,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => __("The password recovery code was sent to the administrator's mobile number."),
                'data' => [
                    'phone' => $user->phone,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Organization forgot password error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'خطا در ارسال کد بازیابی. لطفاً دوباره تلاش کنید.',
            ], 500);
        }
    }

    /**
     * تایید کد بازیابی رمز عبور
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyResetCode(Request $request): JsonResponse
    {
        $request->validate([
            'organization_code' => 'required|string|size:6',
            'code' => 'required|string|size:6',
        ], [
            'organization_code.required' => 'کد سازمانی الزامی است.',
            'organization_code.size' => 'کد سازمانی باید 6 رقم باشد.',
            'code.required' => 'کد تایید الزامی است.',
            'code.size' => 'کد تایید باید 6 رقم باشد.',
        ]);

        try {
            $organizationRepository = app(\App\Repositories\OrganizationRepository::class);

            // پیدا کردن سازمان
            $organization = $organizationRepository->findByOrganizationCode($request->input('organization_code'));

            if (!$organization) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'کد سازمانی یافت نشد.',
                ], 404);
            }

            $user = $organization->user;

            // تایید کد
            $result = $this->verificationService->verifyPhone(
                $user->phone,
                $request->input('code'),
                true // این برای password reset است
            );

            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'کد تایید صحیح است. می‌توانید رمز عبور جدید را وارد کنید.',
                'data' => [
                    'phone' => $user->phone,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Organization verify reset code error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'خطا در تایید کد.',
            ], 500);
        }
    }

    /**
     * تنظیم رمز عبور جدید
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'organization_code' => 'required|string',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|max:64',
        ], [
            'organization_code.required' => 'کد سازمانی الزامی است.', 
            'code.required' => 'کد تایید الزامی است.',
            'code.size' => 'کد تایید باید 6 رقم باشد.',
            'password.required' => 'رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل 8 کاراکتر باشد.',
        ]);

        try {
            $organizationRepository = app(\App\Repositories\OrganizationRepository::class);

            // پیدا کردن سازمان
            $organization = $organizationRepository->findByOrganizationCode($request->input('organization_code'));

            if (!$organization) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'کد سازمانی یافت نشد.',
                ], 404);
            }

            $user = $organization->user;

            // بررسی کد تایید
            if (!$this->userRepository->checkVerificationCode($user->phone, $request->input('code'))) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'کد تایید نادرست یا منقضی شده است.',
                ], 400);
            }

            // تنظیم رمز عبور جدید
            $updated = $this->userRepository->update($user->id, [
                'password' => bcrypt($request->input('password')),
                'phone_verify_code' => null, // پاک کردن کد تایید
            ]);

            if (!$updated) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'خطا در تنظیم رمز عبور جدید.',
                ], 500);
            }

            Log::info('Organization password reset successfully', [
                'organization_id' => $organization->id,
                'organization_code' => $organization->organization_code,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'رمز عبور با موفقیت تغییر کرد.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Organization reset password error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'خطا در تنظیم رمز عبور جدید.',
            ], 500);
        }
    }
}
