<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserTransaction;

class WalletRepository
{
    /**
     * افزایش موجودی کیف پول
     */
    public function increaseWallet(int $userId, float $amount): bool
    {
        $user = User::find($userId);

        if (!$user) {
            return false;
        }

        $user->wallet += $amount;
        return $user->save();
    }

    /**
     * کاهش موجودی کیف پول
     */
    public function decreaseWallet(int $userId, float $amount): bool
    {
        $user = User::find($userId);

        if (!$user) {
            return false;
        }

        if ($user->wallet < $amount) {
            return false;
        }

        $user->wallet -= $amount;
        return $user->save();
    }

    /**
     * دریافت موجودی کیف پول
     */
    public function getWalletBalance(int $userId): ?float
    {
        $user = User::find($userId);
        return $user ? $user->wallet : null;
    }

    /**
     * ثبت تراکنش
     */
    public function createTransaction(array $data): UserTransaction
    {
        return UserTransaction::create($data);
    }

    /**
     * دریافت تراکنش‌های کاربر
     */
    public function getUserTransactions(int $userId, int $perPage = 20, ?string $fromDate = null, ?string $toDate = null)
    {
        $query = UserTransaction::where('user_id', $userId);

        // فیلتر بر اساس تاریخ شروع
        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        // فیلتر بر اساس تاریخ پایان
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * پیدا کردن تراکنش با شماره پیگیری
     */
    public function findTransactionByReferenceId(string $referenceId): ?UserTransaction
    {
        return UserTransaction::where('referenceId', $referenceId)->first();
    }

    /**
     * پیدا کردن تراکنش با شناسه
     */
    public function findTransactionById(int $transactionId): ?UserTransaction
    {
        return UserTransaction::find($transactionId);
    }

    /**
     * پیدا کردن آخرین تراکنش در انتظار
     */
    public function findLatestPendingTransaction(): ?UserTransaction
    {
        return UserTransaction::where('status', 0)
            ->where('type', 1) // فقط تراکنش‌های شارژ
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * به‌روزرسانی وضعیت تراکنش
     */
    public function updateTransactionStatus(int $transactionId, int $status, ?string $referenceId = null): bool
    {
        $transaction = UserTransaction::find($transactionId);

        if (!$transaction) {
            return false;
        }

        $transaction->status = $status;

        if ($referenceId) {
            $transaction->referenceId = $referenceId;
        }

        return $transaction->save();
    }

    /**
     * بررسی موجودی کافی در کیف پول
     */
    public function hasEnoughBalance(int $userId, float $amount): bool
    {
        $balance = $this->getWalletBalance($userId);

        if ($balance === null) {
            return false;
        }

        return $balance >= $amount;
    }

    /**
     * کاهش موجودی و ثبت تراکنش (برای پرداخت سفارش)
     */
    public function deductForPayment(int $userId, int $orderId, float $amount, string $pay_type): array
    {
        $user = User::find($userId);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'کاربر یافت نشد.'
            ];
        }

        if ($user->wallet < $amount) {
            return [
                'success' => false,
                'message' => 'موجودی کیف پول کافی نیست.'
            ];
        }

        $user->wallet -= $amount;
        $user->save();

        // ثبت تراکنش پرداخت
        $transaction = $this->createTransaction([
            'user_id' => $userId,
            'price' => $amount,
            'referenceId' => 'ORDER_' . $orderId . '_' . time(),
            'type' => 3, // پرداخت
            'status' => 100, // موفق
            'description' => $pay_type == 'pay_type' ? 'پیش پرداخت سفارش شماره' . $orderId : 'پرداخت سفارش شماره ' . $orderId,
        ]);

        return [
            'success' => true,
            'transaction' => $transaction,
            'remaining_balance' => $user->wallet
        ];
    }

    public function findPendingByReferenceId(string $trackId): ?UserTransaction
    {
        return UserTransaction::where('referenceId', $trackId)
            ->where('status', 0) // در انتظار
            ->where('type', 1)   // شارژ
            ->first();
    }
}

