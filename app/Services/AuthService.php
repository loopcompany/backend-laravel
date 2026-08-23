<?php

namespace App\Services;

use App\DTOs\LoginDTO;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    /**
     * Authenticate user and return token
     */
    public function login(LoginDTO $dto): array
    {
        try {
            // Find user by phone
            $user = $this->userRepository->findByPhone($dto->phone);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'شماره موبایل یا رمز عبور اشتباه است.',
                ];
            }

            // Check if user has access
            if (!$user->hasAccess()) {
                return [
                    'success' => false,
                    'message' => 'دسترسی کاربر مسدود شده است.',
                ];
            }

            // Check password
            if (!Hash::check($dto->password, $user->password)) {
                return [
                    'success' => false,
                    'message' => 'شماره موبایل یا رمز عبور اشتباه است.',
                ];
            }

            // Check if password exists (user might not have set password yet)
            if (is_null($user->password)) {
                return [
                    'success' => false,
                    'message' => 'لطفاً ابتدا رمز عبور خود را تنظیم کنید.',
                ];
            }

            // Check if phone is verified
            if (!$user->isPhoneVerified()) {
                return [
                    'success' => false,
                    'message' => 'لطفاً ابتدا شماره موبایل خود را تایید کنید.',
                    'requires_verification' => true,
                ];
            }

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;

            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'phone' => $dto->phone
            ]);

            return [
                'success' => true,
                'message' => 'ورود با موفقیت انجام شد.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'last_name' => $user->last_name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'phone_verified_at' => $user->phone_verified_at?->toISOString(),
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ];

        } catch (Exception $e) {
            Log::error('Login failed', [
                'phone' => $dto->phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ورود. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Logout user by revoking current token
     */
    public function logout(string $tokenId): array
    {
        try {
            // Find and delete the token
            $token = PersonalAccessToken::findToken($tokenId);
            
            if ($token) {
                $userId = $token->tokenable_id;
                $token->delete();

                Log::info('User logged out successfully', [
                    'user_id' => $userId
                ]);

                return [
                    'success' => true,
                    'message' => __("Exit was successful."),
                ];
            }

            return [
                'success' => false,
                'message' => 'توکن معتبر نیست.',
            ];

        } catch (Exception $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در خروج. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Revoke all tokens for a user
     */
    public function logoutFromAllDevices(int $userId): array
    {
        try {
            $user = $this->userRepository->findById($userId);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'کاربر یافت نشد.',
                ];
            }

            // Delete all tokens
            $user->tokens()->delete();

            Log::info('User logged out from all devices', [
                'user_id' => $userId
            ]);

            return [
                'success' => true,
                'message' => 'خروج از همه دستگاه‌ها با موفقیت انجام شد.',
            ];

        } catch (Exception $e) {
            Log::error('Logout from all devices failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در خروج از همه دستگاه‌ها. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ];
        }
    }
}