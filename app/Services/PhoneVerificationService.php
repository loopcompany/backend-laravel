<?php

namespace App\Services;

use App\Services\SecurePasswordService;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PhoneVerificationService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected SecurePasswordService $securePasswordService
    ) {
    }

    /**
     * Verify phone number with provided code
     */
    public function verifyPhone(string $phone, string $code, bool $isPasswordReset = false): array
    {
        try {
            return DB::transaction(function () use ($phone, $code, $isPasswordReset) {
                // Find user by phone
                $user = $this->userRepository->findByPhone($phone);

                if (!$user) {
                    return [
                        'success' => false,
                        'message' => 'کاربر با این شماره موبایل یافت نشد.',
                    ];
                }

                // Check if already verified (only for regular verification, not password reset)
                if (!$isPasswordReset && $user->phone_verified_at) {
                    return [
                        'success' => false,
                        'message' => 'شماره موبایل قبلاً تایید شده است.',
                    ];
                }

                // Check if verification code exists
                if (!$user->phone_verify_code) {
                    return [
                        'success' => false,
                        'message' => 'کد تایید برای این شماره یافت نشد.',
                    ];
                }

                // Verify the code
                if (!$this->userRepository->checkVerificationCode($phone, $code)) {
                    return [
                        'success' => false,
                        'message' => 'کد تایید نادرست است.',
                    ];
                }

                // Verify phone
                $verified = $this->userRepository->verifyPhone($phone);

                if (!$verified) {
                    throw new Exception('Failed to update verification status');
                }

                Log::info('Phone verified successfully', [
                    'user_id' => $user->id,
                    'phone' => $phone,
                    'is_password_reset' => $isPasswordReset
                ]);

                // If this is regular phone verification (not password reset), generate and send secure password
                // But skip password generation for organization users who already have a password
                $passwordGenerated = false;
                $organizationWelcomeSent = false;
                if (!$isPasswordReset) {
                    // Check if user is organization type - they already set their password during registration
                    if ($user->account_type === 'g_organization' || $user->account_type === 's_g_organization' || $user->account_type === 'company' || $user->account_type === 'organization') {
                        Log::info('Skipping password generation for organization user', [
                            'user_id' => $user->id,
                            'phone' => $phone,
                            'account_type' => $user->account_type
                        ]);

                        // Send welcome SMS to organization with their organization code
                        $organization = $user->organization;
                        if ($organization) {
                            $smsService = app(SmsService::class);
                            $organizationWelcomeSent = $smsService->sendOrganizationWelcome(
                                $phone,
                                $organization->organization_name,
                                $organization->organization_code
                            );

                            Log::info('Organization welcome SMS send result', [
                                'user_id' => $user->id,
                                'phone' => $phone,
                                'organization_code' => $organization->organization_code,
                                'sms_sent' => $organizationWelcomeSent
                            ]);
                        }
                    } else {
                        // Generate secure password only for individual users
                        Log::info('Generating secure password after phone verification', [
                            'user_id' => $user->id,
                            'phone' => $phone,
                            'account_type' => $user->account_type ?? 'individual'
                        ]);

                        $passwordResult = $this->securePasswordService->setSecurePasswordForUser($user);
                        $passwordGenerated = $passwordResult['success'] ?? false;

                        Log::info('Secure password generation result', [
                            'user_id' => $user->id,
                            'phone' => $phone,
                            'password_generated' => $passwordGenerated,
                            'sms_sent' => $passwordResult['sms_sent'] ?? false
                        ]);
                    }
                }

                // Set appropriate message based on user type and action
                if ($isPasswordReset) {
                    $message = 'کد تایید صحیح است. وارد شدید.';
                } else {
                    if ($user->account_type === 'organization') {
                        $message = $organizationWelcomeSent
                            ? 'شماره موبایل با موفقیت تایید شد. اطلاعات ورود به شماره شما ارسال گردید.'
                            : 'شماره موبایل با موفقیت تایید شد. می‌توانید با کد سازمانی و رمز عبور خود وارد شوید.';
                    } else {
                        $message = $passwordGenerated
                            ? 'شماره موبایل با موفقیت تایید شد. رمز عبور به شماره شما ارسال گردید.'
                            : 'شماره موبایل با موفقیت تایید شد.';
                    }
                }

                return [
                    'success' => true,
                    'message' => $message,
                    'user' => $user->fresh(), // Get updated user data
                    'user_id' => $user->id,
                    'verified_at' => now()->toISOString(),
                    'password_generated' => $passwordGenerated,
                    'organization_welcome_sent' => $organizationWelcomeSent ?? false,
                ];
            });

        } catch (Exception $e) {
            Log::error('Phone verification failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تایید شماره موبایل. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Resend verification code
     */
    public function resendVerificationCode(string $phone, SmsService $smsService, bool $isPasswordReset = false): array
    {
        try {
            $user = $this->userRepository->findByPhone($phone);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'کاربر با این شماره موبایل یافت نشد.',
                ];
            }

            // Check if already verified (only for regular verification, not password reset)
            if (!$isPasswordReset && $user->phone_verified_at) {
                return [
                    'success' => false,
                    'message' => 'شماره موبایل قبلاً تایید شده است.',
                ];
            }

            // Generate new verification code
            $verificationCode = $smsService->generateVerificationCode();
            $hashedCode = $smsService->hashVerificationCode($verificationCode);

            // Update verification code
            $this->userRepository->updateVerificationCode($phone, $hashedCode);

            // Send SMS
            $smsSent = $smsService->sendVerificationCode($phone, $verificationCode);

            if (!$smsSent) {
                throw new Exception('Failed to send verification SMS');
            }

            Log::info('Verification code resent', [
                'user_id' => $user->id,
                'phone' => $phone,
                'is_password_reset' => $isPasswordReset
            ]);

            return [
                'success' => true,
                'message' => 'کد تایید مجدداً ارسال شد.',
            ];

        } catch (Exception $e) {
            Log::error('Resend verification code failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ارسال مجدد کد تایید. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ];
        }
    }
}