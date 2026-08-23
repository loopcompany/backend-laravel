<?php

namespace App\Services;

use App\Repositories\GemTransactionRepository;
use App\Repositories\DiscountCodeRepository;

class UserGemDiscountService
{
    public function __construct(
        private GemTransactionRepository $gemTransactionRepo,
        private DiscountCodeRepository $discountCodeRepo
    ) {}

    /**
     * دریافت تراکنش‌های gem یک کاربر
     */
    public function getUserGemTransactions(int $userId): array
    {
        $transactions = $this->gemTransactionRepo->getAllUserTransactions($userId);
        
        return [
            'success' => true,
            'data' => $transactions
        ];
    }

    /**
     * دریافت کدهای تخفیف یک کاربر
     */
    public function getUserDiscounts(int $userId): array
    {
        $discounts = $this->discountCodeRepo->getAllUserDiscounts($userId);
        
        return [
            'success' => true,
            'data' => $discounts
        ];
    }
}
