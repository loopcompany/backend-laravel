<?php

namespace App\Repositories;

use App\Models\DiscountCode;
use Illuminate\Support\Collection;

class DiscountCodeRepository
{
    /**
     * بررسی اینکه آیا کاربر قبلاً این کد تخفیف را دریافت کرده
     */
    public function hasUserReceivedDiscount(int $userId, int $clubId): bool
    {
        return DiscountCode::where('user_id', $userId)
            ->where('club_id', $clubId)
            ->exists();
    }

    /**
     * دریافت تمام کدهای تخفیف کاربر برای یک کلاب
     */
    public function getUserClubDiscounts(int $userId, int $clubId): Collection
    {
        return DiscountCode::where('user_id', $userId)
            ->where('club_id', $clubId)
            ->get();
    }

    /**
     * ایجاد کد تخفیف جدید
     */
    public function create(array $data): DiscountCode
    {
        return DiscountCode::create($data);
    }

    /**
     * دریافت کدهای تخفیف فعال کاربر
     */
    public function getUserActiveDiscounts(int $userId): Collection
    {
        return DiscountCode::where('user_id', $userId)
            ->where('expiry_date', '>', now())
            ->where('count', '>', 0)
            ->with('club')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * دریافت تمام کدهای تخفیف یک کاربر
     */
    public function getAllUserDiscounts(int $userId): Collection
    {
        return DiscountCode::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->with(['club', 'discount_use'])
            ->get();
    }

    /**
     * پیدا کردن کد تخفیف بر اساس کد و کاربر
     */
    public function findByCodeAndUser(string $code, int $userId): ?DiscountCode
    {
        return DiscountCode::where('code', $code)
            ->where('user_id', $userId)
            ->with(['club.category'])
            ->first();
    }

    /**
     * پیدا کردن کد تخفیف بر اساس کد
     */
    public function findByCode(string $code): ?DiscountCode
    {
        return DiscountCode::where('code', $code)
            ->with(['club.category'])
            ->first();
    }
}
