<?php

namespace App\Services;

use App\DTOs\RegistrationDTO;
use App\Models\User;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrationService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected SmsService $smsService
    ) {}

    /**
     * Register a new user and send verification SMS
     */
    public function register(RegistrationDTO $dto): array
    {
        try {
            return DB::transaction(function () use ($dto) {
                // Check if user already exists but not verified
                $existingUser = $this->userRepository->findByPhone($dto->phone);
                
                $user = null;
                $isUpdate = false;
                
                if ($existingUser && !$existingUser->phone_verified_at) {
                    // User exists but not verified - update existing record
                    $isUpdate = true;
                    
                    // Prepare update data (keep existing referral code)
                    $userData = [
                        'melicode' => $dto->melicode,
                        'email' => $dto->email,
                        'other_referral_code' => $dto->other_referral_code,
                        'province_id' => $dto->province_id,
                        'city_id' => $dto->city_id,
                        'region_id' => $dto->region_id,
                        // Keep existing referral_code and phone
                        // Reset verification fields
                        'phone_verified_at' => null,
                        'phone_verify_code' => null,
                    ];
                    
                    // Update existing user
                    $user = $this->userRepository->update($existingUser->id, $userData);
                    
                    Log::info('Updated existing unverified user', [
                        'user_id' => $existingUser->id,
                        'phone' => $dto->phone,
                        'previous_email' => $existingUser->email,
                        'new_email' => $dto->email,
                        'previous_melicode' => $existingUser->melicode,
                        'new_melicode' => $dto->melicode,
                    ]);
                    
                } elseif ($existingUser && $existingUser->phone_verified_at) {
                    // User exists and already verified
                    return [
                        'success' => false,
                        'message' => 'این شماره موبایل قبلاً ثبت و تایید شده است.',
                        'error' => 'phone_already_verified',
                        'user_id' => $existingUser->id,
                    ];
                    
                } else {
                    // New user - create new record
                    $isUpdate = false;
                    
                    // Generate unique referral code
                    $referralCode = $this->userRepository->generateUniqueReferralCode();

                    // Create user data
                    $userData = [
                        'melicode' => $dto->melicode,
                        'phone' => $dto->phone,
                        'email' => $dto->email,
                        'other_referral_code' => $dto->other_referral_code,
                        'province_id' => $dto->province_id,
                        'city_id' => $dto->city_id,
                        'region_id' => $dto->region_id,
                        'referral_code' => $referralCode,
                    ];

                    // Create user
                    $user = $this->userRepository->create($userData);
                    
                    Log::info('Created new user', [
                        'user_id' => $user->id,
                        'phone' => $dto->phone,
                        'email' => $dto->email,
                        'referral_code' => $referralCode,
                    ]);
                }

                if (!$user) {
                    throw new Exception('Failed to create or update user');
                }

                // Generate and send verification code
                $verificationCode = $this->smsService->generateVerificationCode();
                $hashedCode = $this->smsService->hashVerificationCode($verificationCode);

                Log::info('Generated verification code', [
                    'user_id' => $user->id,
                    'phone' => $dto->phone,
                    'code_length' => strlen($verificationCode),
                    'timestamp' => now()->toDateTimeString()
                ]);

                // Update user with verification code
                $this->userRepository->updateVerificationCode($dto->phone, $hashedCode);

                Log::info('Updated user with verification code', [
                    'user_id' => $user->id,
                    'phone' => $dto->phone,
                    'timestamp' => now()->toDateTimeString()
                ]);

                // Send SMS
                Log::info('Starting SMS send process', [
                    'user_id' => $user->id,
                    'phone' => $dto->phone,
                    'timestamp' => now()->toDateTimeString()
                ]);
                
                $smsSent = $this->smsService->sendVerificationCode($dto->phone, $verificationCode, $dto->hashApp);

                Log::info('SMS send completed', [
                    'user_id' => $user->id,
                    'phone' => $dto->phone,
                    'sms_sent' => $smsSent,
                    'timestamp' => now()->toDateTimeString()
                ]);

                if (!$smsSent) {
                    Log::error('SMS send failed - throwing exception', [
                        'user_id' => $user->id,
                        'phone' => $dto->phone,
                        'timestamp' => now()->toDateTimeString()
                    ]);
                    throw new Exception('Failed to send verification SMS');
                }

                $actionLog = $isUpdate ? 'User updated and verification resent' : 'User registered successfully';
                Log::info($actionLog, [
                    'user_id' => $user->id,
                    'phone' => $dto->phone,
                    'melicode' => $dto->melicode,
                    'is_update' => $isUpdate
                ]);

                $message = $isUpdate 
                    ? 'اطلاعات شما به‌روزرسانی شد. کد تایید مجدد به شماره شما ارسال گردید.'
                    : 'ثبت نام با موفقیت انجام شد. کد تایید به شماره شما ارسال گردید.';

                return [
                    'success' => true,
                    'message' => $message,
                    'user_id' => $user->id,
                    'phone' => $dto->phone,
                    'is_update' => $isUpdate,
                ];
            });

        } catch (Exception $e) {
            Log::error('Registration failed', [
                'phone' => $dto->phone,
                'melicode' => $dto->melicode,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت نام. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate referral code
     */
    public function validateReferralCode(?string $referralCode, string $userPhone): bool
    {
        if (!$referralCode) {
            return true; // Optional field
        }

        $referralUser = $this->userRepository->findByReferralCode($referralCode);
        
        if (!$referralUser) {
            return false;
        }

        // Check if user is not trying to use their own referral code
        return $referralUser->phone != $userPhone;
    }

    /**
     * Check if user data already exists
     */
    public function checkExistingUser(RegistrationDTO $dto): array
    {
        $errors = [];

        // Check phone number
        $phoneUser = $this->userRepository->findByPhone($dto->phone);
        if ($phoneUser && $phoneUser->phone_verified_at) {
            // Phone exists and is verified
            $errors['phone'] = 'این شماره موبایل قبلاً ثبت و تایید شده است.';
        }
        // If phone exists but not verified, we allow re-registration (handled in register method)

        // Check melicode - but allow update for unverified users
        $melicodeUser = $this->userRepository->findByMelicode($dto->melicode);
        if ($melicodeUser) {
            if ($melicodeUser->phone_verified_at) {
                // Melicode exists and user is verified
                $errors['melicode'] = 'این کد ملی قبلاً ثبت و تایید شده است.';
            } elseif ($melicodeUser->phone != $dto->phone) {
                // Melicode exists but belongs to different unverified phone
                $errors['melicode'] = 'این کد ملی برای شماره دیگری ثبت شده است.';
            }
            // If melicode belongs to same phone and not verified, allow update
        }

        return $errors;
    }
}