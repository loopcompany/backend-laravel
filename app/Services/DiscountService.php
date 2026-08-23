<?php

namespace App\Services;

use App\Repositories\ClubRepository;
use App\Repositories\DiscountCodeRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\GemTransactionRepository;
use App\Repositories\GemActionRepository;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiscountService
{
    public function __construct(
        private ClubRepository $clubRepo,
        private DiscountCodeRepository $discountCodeRepo,
        private CategoryRepository $categoryRepo,
        private GemTransactionRepository $gemTransactionRepo,
        private GemActionRepository $gemActionRepo
    ) {}

    /**
     * دریافت پیشنهادات هفتگی
     */
    public function getWeeklyOffers(): array
    {
        try {
            $clubs = $this->clubRepo->getWeeklyClubs();
            
            return [
                'success' => true,
                'data' => $clubs
            ];
        } catch (\Exception $e) {
            Log::error('خطا در دریافت پیشنهادات هفتگی: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در دریافت پیشنهادات هفتگی'
            ];
        }
    }

    /**
     * دریافت دسته‌بندی‌های دارای تخفیف
     */
    public function getDiscountCategories(): array
    {
        try {
            $categories = $this->categoryRepo->getMainCategoriesWithClubDescendants();
            
            return [
                'success' => true,
                'data' => $categories
            ];
        } catch (\Exception $e) {
            Log::error('خطا در دریافت دسته‌بندی‌های تخفیف: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در دریافت دسته‌بندی‌های تخفیف'
            ];
        }
    }

    /**
     * دریافت لیست تمام تخفیف‌ها
     */
    public function getAllDiscounts(): array
    {
        try {
            $clubs = $this->clubRepo->getAllClubsWithCategory();
            
            // اضافه کردن category_id والد اصلی به هر کلاب
            $data = $clubs->map(function ($club) {
                $club['category_id'] = $club->category->getAllParents()->last()->id ?? $club->category_id;
                return $club;
            });
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (\Exception $e) {
            Log::error('خطا در دریافت لیست تخفیف‌ها: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست تخفیف‌ها'
            ];
        }
    }

    /**
     * دریافت تخفیف تایم‌دار فعال
     */
    public function getActiveTimedDiscount(): array
    {
        try {
            $club = $this->clubRepo->getActiveTimedDiscount();
            
            return [
                'success' => true,
                'data' => $club
            ];
        } catch (\Exception $e) {
            Log::error('خطا در دریافت تخفیف تایم‌دار: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در دریافت تخفیف تایم‌دار'
            ];
        }
    }

    /**
     * دریافت جزئیات تخفیف
     */
    public function getDiscountDetail(int $discountId): array
    {
        try {
            $club = $this->clubRepo->findById($discountId);
            
            if (!$club) {
                return [
                    'success' => false,
                    'message' => 'تخفیف یافت نشد',
                    'error_code' => 'DISCOUNT_NOT_FOUND'
                ];
            }
            
            return [
                'success' => true,
                'data' => $club
            ];
        } catch (\Exception $e) {
            Log::error('خطا در دریافت جزئیات تخفیف: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در دریافت جزئیات تخفیف'
            ];
        }
    }

    /**
     * دریافت کد تخفیف توسط کاربر
     */
    public function claimDiscount(int $userId, int $discountId): array
    {
        DB::beginTransaction();
        
        try {
            // بررسی وجود کلاب
            $club = $this->clubRepo->findById($discountId);
            if (!$club) {
                return [
                    'success' => false,
                    'message' => 'شناسه نامعتبر است.',
                    'error_code' => 'INVALID_DISCOUNT_ID'
                ];
            }

            // بررسی دریافت قبلی
            $hasReceived = $this->discountCodeRepo->hasUserReceivedDiscount($userId, $discountId);
            if ($hasReceived) {
                return [
                    'success' => false,
                    'message' => 'شما قبلاً این تخفیف را دریافت کرده‌اید.',
                    'error_code' => 'ALREADY_CLAIMED'
                ];
            }

            // بررسی موجودی امتیاز کاربر
            $userGems = $this->gemTransactionRepo->getTotalUserGems($userId);
            if ($userGems < $club->gems) {
                return [
                    'success' => false,
                    'message' => 'امتیازات شما برای دریافت این تخفیف کافی نمی‌باشد.',
                    'error_code' => 'INSUFFICIENT_GEMS'
                ];
            }

            // تولید کد تخفیف یکتا
            $code = $this->generateUniqueDiscountCode();

            // ایجاد کد تخفیف
            $discount = $this->discountCodeRepo->create([
                'user_id' => $userId,
                'club_id' => $discountId,
                'discount_percent' => $club->discount_percent,
                'count' => $club->count,
                'code' => $code,
                'expiry_date' => now()->addDays($club->expire),
            ]);

            // کسر امتیاز از کاربر
            // پیدا کردن gem_action مناسب (مطابق با مقدار gems یا استفاده از یک fallback)
            $gemAction = $this->gemActionRepo->findByGems(abs($club->gems));
            if (!$gemAction) {
                // اگر موردی با مقدار دقیق پیدا نشد، از کوچکترین مقدار فعال استفاده کن
                $actions = $this->gemActionRepo->getActiveActions();
                $gemAction = $actions->last();
            }

            $gemTransaction = $this->gemTransactionRepo->createTransaction([
                'user_id' => $userId,
                'gem_action_id' => $gemAction->id,
                'gems' => -$club->gems, // منفی برای کسر
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'کد تخفیف شما با موفقیت ایجاد شد.',
                'data' => [
                    'code' => $discount->code,
                    'discount_percent' => $discount->discount_percent,
                    'expiry_date' => $discount->expiry_date->format('Y-m-d H:i:s'),
                    'remaining_gems' => $userGems - $club->gems,
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('خطا در دریافت کد تخفیف: ' . $e->getMessage(), [
                'userId' => $userId,
                'discountId' => $discountId
            ]);
            
            return [
                'success' => false,
                'message' => 'خطا در ایجاد کد تخفیف',
                'error_code' => 'CLAIM_ERROR'
            ];
        }
    }

    /**
     * بررسی اعتبار کد تخفیف
     */
    public function validateDiscountCode(string $code, int $categoryId, int $userId): array
    {
        try {
            // پیدا کردن کد تخفیف
            $discountCode = $this->discountCodeRepo->findByCodeAndUser($code, $userId);

            if (!$discountCode) {
                return [
                    'success' => false,
                    'message' => 'کد وارد شده صحیح نمی‌باشد'
                ];
            }

            // بررسی تعداد مجاز استفاده
            if ($discountCode->count < 1) {
                return [
                    'success' => false,
                    'message' => 'تعداد دفعات مجاز استفاده به پایان رسیده است.'
                ];
            }

            // بررسی تاریخ انقضا
            if ($discountCode->expiry_date < now()) {
                return [
                    'success' => false,
                    'message' => 'زمان مجاز استفاده به پایان رسیده است.'
                ];
            }

            // بررسی دسته‌بندی
            if ((int) $discountCode->club->category->id != (int) $categoryId) {
                return [
                    'success' => false,
                    'message' => 'مجاز به استفاده در این دسته بندی نیستید'
                ];
            }

            return [
                'success' => true,
                'message' => 'تخفیف با موفقیت اعمال شد',
                'data' => [
                    'discount_code_id' => $discountCode->id,
                    'discount_percent' => $discountCode->discount_percent
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در بررسی کد تخفیف: ' . $e->getMessage(), [
                'code' => $code,
                'userId' => $userId,
                'categoryId' => $categoryId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در بررسی کد تخفیف'
            ];
        }
    }

    /**
     * تولید کد تخفیف یکتا
     */
    private function generateUniqueDiscountCode(): string
    {
        do {
            $letters = Str::upper(Str::random(3));
            $numbers = mt_rand(100000, 999999);
            $code = $letters . '-' . $numbers;
            
            // بررسی یکتا بودن
            $exists = DB::table('discount_codes')->where('code', $code)->exists();
        } while ($exists);
        
        return $code;
    }
}
