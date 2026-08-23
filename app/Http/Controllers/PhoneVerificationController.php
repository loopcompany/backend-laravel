<?php

namespace App\Http\Controllers;

use App\Http\Requests\VerifyPhoneRequest;
use App\Services\PhoneVerificationService;
use App\Services\SecurePasswordService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PhoneVerificationController extends Controller
{
    public function __construct(
        protected PhoneVerificationService $verificationService,
        protected SmsService $smsService,
        protected SecurePasswordService $securePasswordService
    ) {
    }

    /**
     * Verify phone number with provided code
     */
    public function verify(VerifyPhoneRequest $request): JsonResponse
    {
        try {
            $phone = $request->validated('phone');
            $code = $request->validated('verification_code');

            $result = $this->verificationService->verifyPhone($phone, $code);
            Log::info('res:', $result);
            if ($result['success']) {
                // Get the verified user
                $user = $result['user'];

                // Generate and send secure password only for non-organization users
                $passwordResult = null;
           

                // Create authentication token
                $token = $user->createToken('mobile_app')->plainTextToken;

                // Prepare response message
                $message = $result['message'];
                if ($passwordResult && $passwordResult['success']) {
                    $message .= ' ' . $passwordResult['message'];
                } elseif ($passwordResult && !$passwordResult['success']) {
                    $message .= ' اما ' . $passwordResult['message'];
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'last_name' => $user->last_name,
                            'phone' => $user->phone,
                            'email' => $user->email,
                            'melicode' => $user->melicode,
                            'phone_verified_at' => $user->phone_verified_at?->toISOString(),
                            'email_verified_at' => $user->email_verified_at?->toISOString(),
                            'referral_code' => $user->referral_code,
                            'other_referral_code' => $user->other_referral_code,
                            'has_access' => $user->hasAccess(),
                            'is_phone_verified' => $user->isPhoneVerified(),
                            'created_at' => $user->created_at?->toISOString(),
                            'updated_at' => $user->updated_at?->toISOString(),
                        ],
                        'token' => $token,
                        'token_type' => 'Bearer',
                        // 'verified_at' => $result['verified_at'],
                        // 'secure_password' => [
                        //     'generated' => $passwordResult['password_generated'],
                        //     'sms_sent' => $passwordResult['sms_sent'],
                        // ]
                    ]
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در تایید شماره موبایل. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resend verification code
     */
    public function resend(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'phone' => [
                    'required',
                    'string',
                    'regex:/^09[0-9]{9}$/',
                    'exists:users,phone',
                ],
            ], [
                'phone.required' => 'شماره موبایل الزامی است.',
                'phone.regex' => 'فرمت شماره موبایل صحیح نیست.',
                'phone.exists' => 'شماره موبایل در سیستم یافت نشد.',
            ]);

            $phone = $request->input('phone');

            $result = $this->verificationService->resendVerificationCode($phone, $this->smsService);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال مجدد کد تایید. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify phone number with provided code for password reset
     */
    public function verifyForPasswordReset(VerifyPhoneRequest $request): JsonResponse
    {
        try {
            $phone = $request->validated('phone');
            $code = $request->validated('verification_code');

            $result = $this->verificationService->verifyPhone($phone, $code, true); // true for password reset

            if ($result['success']) {
                // Get the verified user
                $user = $result['user'];

                // Create authentication token for password reset login
                $token = $user->createToken('password_reset_login')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'last_name' => $user->last_name,
                            'phone' => $user->phone,
                            'email' => $user->email,
                            'phone_verified_at' => $user->phone_verified_at?->toISOString(),
                            'referral_code' => $user->referral_code,
                            'other_referral_code' => $user->other_referral_code,
                        ],
                        'token' => $token,
                        'token_type' => 'Bearer',
                    ]
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در تایید کد بازیابی. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resend verification code for password reset
     */
    public function resendForPasswordReset(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'phone' => [
                    'required',
                    'string',
                    'regex:/^09[0-9]{9}$/',
                    'exists:users,phone',
                ],
            ], [
                'phone.required' => 'شماره موبایل الزامی است.',
                'phone.regex' => 'فرمت شماره موبایل صحیح نیست.',
                'phone.exists' => 'شماره موبایل در سیستم یافت نشد.',
            ]);

            $phone = $request->input('phone');

            $result = $this->verificationService->resendVerificationCode($phone, $this->smsService, true); // true for password reset

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error' => $result['error'] ?? null
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال مجدد کد بازیابی. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}