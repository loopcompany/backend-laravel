<?php

namespace App\Repositories;

use App\Models\GemTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GemTransactionRepository
{
    /**
     * ثبت تراکنش gem جدید
     */
    public function createTransaction(array $data): GemTransaction
    {
        return GemTransaction::create($data);
    }

    /**
     * دریافت تراکنش‌های یک کاربر
     */
    public function getUserTransactions(int $userId, int $perPage = 20)
    {
        return GemTransaction::where('user_id', $userId)
            ->with('gemAction')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * دریافت تمام تراکنش‌های یک کاربر (بدون صفحه‌بندی)
     */
    public function getAllUserTransactions(int $userId)
    {
        return GemTransaction::with('gem_action')
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * محاسبه مجموع gem های یک کاربر
     */
    public function getTotalUserGems(int $userId): int
    {
        return GemTransaction::where('user_id', $userId)
            ->sum('gems');
    }

    /**
     * بررسی آیا کاربر در هفته جاری gem action انجام داده
     */
    public function hasUserPlayedThisWeek(int $userId): bool
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        return GemTransaction::where('user_id', $userId)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->where('gems', '>=', 0)
            ->exists();
    }

    /**
     * دریافت آخرین تراکنش کاربر
     */
    public function getLastUserTransaction(int $userId): ?GemTransaction
    {
        return GemTransaction::where('user_id', $userId)
            ->latest()
            ->first();
    }

    /**
     * دریافت تراکنش‌های هفته جاری
     */
    public function getThisWeekTransactions(int $userId)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        return GemTransaction::where('user_id', $userId)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->with('gemAction')
            ->get();
    }
}
