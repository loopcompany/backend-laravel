<?php

namespace App\Services;

use App\DTOs\ForgotPasswordDTO;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Support\Facades\Log;

class ForgotPasswordService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected SmsService $smsService
    ) {}

    /**
     * Verify user identity and send password reset code
     */
    public function sendResetCode(ForgotPasswordDTO $dto): array
    {
        try {
            // Find user by phone number
            $user = $this->userRepository->findByPhone($dto->phone);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'اطلاعات وارد شده صحیح نیست.',
                ];
            }

            // Verify user identity with provided information
            if (!$this->verifyUserIdentity($user, $dto)) {
                return [
                    'success' => false,
                    'message' => 'اطلاعات وارد شده با اطلاعات ثبت شده مطابقت ندارد.',
                ];
            }

            // Check if user's phone is verified
            if (!$user->isPhoneVerified()) {
                return [
                    'success' => false,
                    'message' => 'شماره موبایل شما تایید نشده است. لطفاً ابتدا شماره خود را تایید کنید.',
                ];
            }

            // Generate and send reset code (using same SMS system as verification)
            $resetCode = $this->smsService->generateVerificationCode();
            $hashedCode = $this->smsService->hashVerificationCode($resetCode);

            // Update user with reset code (using the same field as phone verification)
            $this->userRepository->updateVerificationCode($dto->phone, $hashedCode);

            // Send SMS
            $smsSent = $this->smsService->sendPasswordResetCode($dto->phone, $resetCode, $dto->hashApp);

            if (!$smsSent) {
                throw new Exception('Failed to send reset code SMS');
            }

            Log::info('Password reset code sent', [
                'user_id' => $user->id,
                'phone' => $dto->phone
            ]);

            return [
                'success' => true,
                'message' => 'کد بازیابی رمز عبور به شماره شما ارسال شد.',
                'phone' => $dto->phone,
            ];

        } catch (Exception $e) {
            Log::error('Password reset code sending failed', [
                'phone' => $dto->phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ارسال کد بازیابی. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify user identity by comparing provided data with stored data
     */
    private function verifyUserIdentity($user, ForgotPasswordDTO $dto): bool
    {
        // Check if all provided information matches stored data
        return $user->melicode == $dto->melicode &&
               $user->phone == $dto->phone &&
               $user->email == $dto->email;
    }

    /**
     * Check if user can request password reset
     */
    public function canRequestReset(string $phone): array
    {
        try {
            $user = $this->userRepository->findByPhone($phone);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'کاربری با این شماره موبایل یافت نشد.',
                ];
            }

            if (!$user->isPhoneVerified()) {
                return [
                    'success' => false,
                    'message' => 'شماره موبایل شما تایید نشده است.',
                ];
            }

            if (!$user->hasAccess()) {
                return [
                    'success' => false,
                    'message' => 'دسترسی حساب کاربری شما مسدود شده است.',
                ];
            }

            return [
                'success' => true,
                'message' => 'امکان درخواست بازیابی رمز عبور وجود دارد.',
            ];

        } catch (Exception $e) {
            Log::error('Password reset eligibility check failed', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در بررسی امکان بازیابی رمز عبور.',
            ];
        }
    }
}
