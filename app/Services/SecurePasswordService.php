<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Support\Facades\Log;

class SecurePasswordService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SmsService $smsService
    ) {
    }

    /**
     * Generate a secure password for user
     */
    public function generateSecurePassword(): string
    {
        $letter = chr(random_int(97, 122));
        $numbers = '';

        for ($i = 0; $i < 5; $i++) {
            $numbers .= random_int(0, 9);
        }

        $password = $letter . $numbers;
        return str_shuffle($password);
    }

    /**
     * Set secure password for user and send via SMS
     */
    public function setSecurePasswordForUser(User $user): array
    {
        try {
            // Generate secure password
            $securePassword = $this->generateSecurePassword();

            // Update user password
            $updateResult = $this->userRepository->update($user->id, [
                'password' => bcrypt($securePassword)
            ]);

            if (!$updateResult) {
                throw new Exception('Failed to update user password');
            }

            // Send password via SMS
            $smsSent = $this->smsService->sendSecurePassword($user->phone, $securePassword);

            if (!$smsSent) {
                Log::warning('Failed to send secure password SMS', [
                    'user_id' => $user->id,
                    'phone' => $user->phone
                ]);
            }

            Log::info('Secure password generated and sent', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'sms_sent' => $smsSent
            ]);

            return [
                'success' => true,
                'password_generated' => true,
                'sms_sent' => $smsSent,
                'message' => $smsSent
                    ? 'رمز امن تولید و به شماره شما ارسال شد.'
                    : 'رمز امن تولید شد اما خطا در ارسال پیامک رخ داد.'
            ];

        } catch (Exception $e) {
            Log::error('Failed to generate secure password', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'password_generated' => false,
                'sms_sent' => false,
                'message' => 'خطا در تولید رمز امن: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate password strength
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'رمز عبور باید حداقل 8 کاراکتر باشد.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'رمز عبور باید شامل حداقل یک حرف کوچک انگلیسی باشد.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'رمز عبور باید شامل حداقل یک حرف بزرگ انگلیسی باشد.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'رمز عبور باید شامل حداقل یک عدد باشد.';
        }

        if (!preg_match('/[!@#$%^&*]/', $password)) {
            $errors[] = 'رمز عبور باید شامل حداقل یک نماد (!@#$%^&*) باشد.';
        }

        return [
            'is_strong' => empty($errors),
            'errors' => $errors,
            'score' => $this->calculatePasswordScore($password)
        ];
    }

    /**
     * Calculate password strength score (0-100)
     */
    private function calculatePasswordScore(string $password): int
    {
        $score = 0;

        // Length bonus
        $score += min(25, strlen($password) * 2);

        // Character variety bonus
        if (preg_match('/[a-z]/', $password))
            $score += 15;
        if (preg_match('/[A-Z]/', $password))
            $score += 15;
        if (preg_match('/[0-9]/', $password))
            $score += 15;
        if (preg_match('/[!@#$%^&*]/', $password))
            $score += 15;

        // Complexity bonus
        if (preg_match('/[a-z].*[A-Z]|[A-Z].*[a-z]/', $password))
            $score += 5;
        if (preg_match('/[a-zA-Z].*[0-9]|[0-9].*[a-zA-Z]/', $password))
            $score += 5;
        if (preg_match('/[a-zA-Z0-9].*[!@#$%^&*]|[!@#$%^&*].*[a-zA-Z0-9]/', $password))
            $score += 5;

        return min(100, $score);
    }
}