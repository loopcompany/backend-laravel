<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Log;

class UserRepository
{
    public function __construct(
        protected User $model
    ) {
    }

    /**
     * Create a new user
     */
    public function create(array $data): User
    {
        return $this->model->create($data);
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?User
    {
        return $this->model->find($id);
    }

    /**
     * Find user by phone number
     */
    public function findByPhone(string $phone): ?User
    {
        return $this->model->where('phone', $phone)->first();
    }

    /**
     * Find user by referral code
     */
    public function findByReferralCode(string $referralCode): ?User
    {
        return $this->model->where('referral_code', $referralCode)->first();
    }

    /**
     * Find user by melicode
     */
    public function findByMelicode(string $melicode): ?User
    {
        return $this->model->where('melicode', $melicode)->first();
    }

    /**
     * Update user by ID
     */
    public function update(int $id, array $data): ?User
    {
        $user = $this->findById($id);

        if (!$user) {
            return null;
        }

        // Handle profile image upload if present
        if (isset($data['profile_photo_path']) && $data['profile_photo_path'] instanceof \Illuminate\Http\UploadedFile) {
            // Delete old image if exists
            if ($user->profile_photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_photo_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
            }

            // Store new image
            $path = $data['profile_photo_path']->store('images/profiles', 'public');
            $data['profile_photo_path'] = $path;
        }

        $user->update($data);

        return $user->fresh();
    }

    /**
     * Update user verification code
     */
    public function updateVerificationCode(string $phone, string $hashedCode): bool
    {
        return $this->model->where('phone', $phone)
            ->update(['phone_verify_code' => $hashedCode]);
    }

    /**
     * Verify phone and mark as verified
     */
    public function verifyPhone(string $phone): bool
    {
        return $this->model->where('phone', $phone)
            ->update([
                'phone_verified_at' => now(),
                'phone_verify_code' => null,
            ]);
    }

    /**
     * Generate unique referral code for user 
     * Format: L + 5 random characters (numbers and uppercase letters)
     */
    public function generateUniqueReferralCode(): string
    {
        do {
            // تولید 5 کاراکتر تصادفی (حروف بزرگ انگلیسی + اعداد)
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $randomString = '';
            for ($i = 0; $i < 5; $i++) {
                $randomString .= $characters[random_int(0, strlen($characters) - 1)];
            }
            $referralCode = 'L' . $randomString;
        } while ($this->model->where('referral_code', $referralCode)->exists());

        return $referralCode;
    }

    /**
     * Check if verification code matches
     */
    public function checkVerificationCode(string $phone, string $code): bool
    {
        $user = $this->findByPhone($phone);

        if (!$user || !$user->phone_verify_code) {
            return false;
        }

        return password_verify($code, $user->phone_verify_code);
    }
    public function createUniquCodeForUsers($cityCode, $regionCode, $user, $startWith): string
    {
        // prefix ثابت
        $uniq_code = $cityCode . $regionCode . '0';

        // m = مجموع startWith + id
        $m = $startWith + $user->id;
        $mStr = (string) $m;

        // رقم اول startWith و m
        $firstDigitMain = (int) ((string) $startWith)[0];
        $firstDigit = (int) $mStr[0];

        // اختلاف
        $diff = $firstDigit - $firstDigitMain; 
        // افزودن صفرهای اضافی
        if ($diff > 0) {
            $uniq_code .= str_repeat('0', $diff);
        }
        $uniq_code .= $firstDigitMain;
        // افزودن بقیه ارقام m به جز رقم اول
        $uniq_code .= substr($mStr, 1);

        return $uniq_code;
    }
}