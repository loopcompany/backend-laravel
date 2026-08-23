<?php

namespace App\Services;

use App\DTOs\UpdateProfileDTO;
use App\Models\Region;
use App\Models\User;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileUpdateService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SmsService $smsService
    ) {}

    /**
     * Update user profile
     */
    public function updateProfile(User $user, UpdateProfileDTO $dto): array
    {
        if (!$dto->hasChanges()) {
            return [
                'success' => false,
                'message' => 'هیچ تغییری برای اعمال وجود ندارد.',
                'requires_verification' => false,
            ];
        }

        try {
            DB::beginTransaction();

            $originalEmail = $user->email;
            
            // Check if email is changing
            $emailChanged = $dto->hasEmailChange() && $dto->email != $originalEmail;

            // Update user data
            $updateData = $dto->toArray();
            
            // Reset email verification if email changed
            if ($emailChanged && $dto->email != null) {
                $updateData['email_verified_at'] = null;
            }

            // Update user
           
            $updatedUser = $this->userRepository->update($user->id, $updateData);

            if (!$updatedUser) {
                throw new Exception('خطا در به‌روزرسانی اطلاعات کاربر.');
            } 
            if(isset($updateData['region']) && is_null($user->code)){
                $code = $this->userRepository->createUniquCodeForUsers(21, $updateData['region'], $user, 5000);
                $user->update(['code'=>$code]);
                $this->smsService->sendUserCode($user->phone, $code);
            }

            DB::commit();

            // Prepare response
            $response = [
                'success' => true,
                'message' => 'اطلاعات شما با موفقیت به‌روزرسانی شد.',
                'user' => [
                    'id' => $updatedUser->id,
                    'name' => $updatedUser->name,
                    'last_name' => $updatedUser->last_name,
                    'phone' => $updatedUser->phone,
                    'email' => $updatedUser->email,
                    'melicode' => $updatedUser->melicode,
                    'phone_verified_at' => $updatedUser->phone_verified_at?->toISOString(),
                    'email_verified_at' => $updatedUser->email_verified_at?->toISOString(),
                    'referral_code' => $updatedUser->referral_code,
                    'other_referral_code' => $updatedUser->other_referral_code,
                    'profile_photo_path' => $updatedUser->profile_photo_path,
                    'has_access' => $updatedUser->hasAccess(),
                    'is_phone_verified' => $updatedUser->isPhoneVerified(),
                    'created_at' => $updatedUser->created_at?->toISOString(),
                    'updated_at' => $updatedUser->updated_at?->toISOString(),
                ],
                'changes' => [
                    'email_changed' => $emailChanged,
                    'password_changed' => $dto->hasPasswordChange(),
                    'profile_data_changed' => $dto->name != null || $dto->last_name != null || $dto->melicode != null,
                ],
                'requires_verification' => false,
            ];

            // Add specific messages for different scenarios
            if ($emailChanged && $dto->email != null) {
                $response['message'] .= ' ایمیل جدید نیاز به تایید دارد.';
            }

            return $response;

        } catch (Exception $e) {
            DB::rollback();
            
            Log::error('Profile update failed', [
                'user_id' => $user->id,
                'dto' => $dto->toArray(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در به‌روزرسانی اطلاعات: ' . $e->getMessage(),
                'requires_verification' => false,
            ];
        }
    }

    /**
     * Get user profile information
     */
    public function getProfile(User $user): array
    {
        $region_info = Region::find($user->region_id);
        return [
            'success' => true,
            'message' => 'اطلاعات پروفایل با موفقیت دریافت شد.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'last_name' => $user->last_name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'melicode' => $user->melicode,
                    'birth_date' => $user->birth_date,
                    'mobile_number' => $user->mobile_number,
                    'phone_number' => $user->phone_number,
                    'postal_code' => $user->postal_code,
                    'city' => $user->city,
                    'region_id' => $region_info,
                    'home_address' => $user->home_address,
                    'work_address' => $user->work_address,
                    'card_number' => $user->card_number,
                    'sheba_number' => $user->sheba_number,
                    'profile_photo_path' => $user->profile_photo_path,
                    'phone_verified_at' => $user->phone_verified_at?->toISOString(),
                    'email_verified_at' => $user->email_verified_at?->toISOString(),
                    'referral_code' => $user->referral_code,
                    'other_referral_code' => $user->other_referral_code,
                    'wallet' => $user->wallet,
                    'total_gems' => $user->total_gems, // ✅ مجموع امتیازات
                    'has_access' => $user->hasAccess(),
                    'is_phone_verified' => $user->isPhoneVerified(),
                    'created_at' => $user->created_at?->toISOString(),
                    'updated_at' => $user->updated_at?->toISOString(),
                ]
            ]
        ];
    }
}