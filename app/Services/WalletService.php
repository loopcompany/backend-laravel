<?php

namespace App\Services;

use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function __construct(
        protected WalletRepository $walletRepo
    ) {
    }

    /**
     * شروع فرآیند شارژ کیف پول
     */
    public function initiateCharge(int $userId, float $amount, ?string $linkingUrl = null): array
    {
        try {
            // اعتبارسنجی مبلغ
            if ($amount <= 0) {
                return [
                    'success' => false,
                    'message' => 'مبلغ باید بیشتر از صفر باشد.',
                    'error_code' => 'INVALID_AMOUNT'
                ];
            }

            // ثبت تراکنش با وضعیت در انتظار
            $transaction = $this->walletRepo->createTransaction([
                'user_id' => $userId,
                'price' => $amount,
                'type' => 1, // شارژ کیف پول
                'status' => 0, // در انتظار
                'description' => 'شارژ کیف پول',
                'linking_url' => $linkingUrl,
            ]);

            Log::info('درخواست شارژ کیف پول ثبت شد', [
                'user_id' => $userId,
                'amount' => $amount,
                'transaction_id' => $transaction->id
            ]);

            return [
                'success' => true,
                'data' => [
                    'transaction_id' => $transaction->id,
                    'amount' => $amount
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در شروع فرآیند شارژ کیف پول', [
                'user_id' => $userId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در شروع فرآیند شارژ',
                'error_code' => 'CHARGE_INIT_ERROR'
            ];
        }
    }

    /**
     * تکمیل فرآیند شارژ موفق
     */
    public function completeSuccessfulCharge(int $userId, float $amount, string $referenceId, ?string $linkingUrl = null): array
    {
        try {
            DB::beginTransaction();

            // پیدا کردن تراکنش pending
            $pendingTransaction = $this->walletRepo->findLatestPendingTransaction();

            if (!$pendingTransaction || $pendingTransaction->user_id != $userId) {
                DB::rollBack();
                Log::warning('تراکنش pending یافت نشد برای تکمیل', [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'reference_id' => $referenceId
                ]);
                return [
                    'success' => false,
                    'message' => 'تراکنش یافت نشد.',
                    'error_code' => 'TRANSACTION_NOT_FOUND'
                ];
            }

            // افزایش موجودی کیف پول
            $walletUpdated = $this->walletRepo->increaseWallet($userId, (float) $amount);

            if (!$walletUpdated) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'خطا در به‌روزرسانی کیف پول',
                    'error_code' => 'WALLET_UPDATE_ERROR'
                ];
            }

            // به‌روزرسانی تراکنش pending به موفق
            $transactionUpdated = $this->walletRepo->updateTransactionStatus(
                $pendingTransaction->id,
                100, // موفق
                $referenceId
            );

            if (!$transactionUpdated) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'خطا در به‌روزرسانی تراکنش',
                    'error_code' => 'TRANSACTION_UPDATE_ERROR'
                ];
            }

            DB::commit();

            Log::info('شارژ کیف پول موفق', [
                'user_id' => $userId,
                'amount' => $amount,
                'reference_id' => $referenceId,
                'transaction_id' => $pendingTransaction->id
            ]);

            return [
                'success' => true,
                'message' => 'کیف پول با موفقیت شارژ شد.',
                'data' => [
                    'wallet_balance' => $this->walletRepo->getWalletBalance($userId),
                    'transaction_id' => $pendingTransaction->id,
                    'reference_id' => $referenceId
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('خطا در تکمیل شارژ کیف پول', [
                'user_id' => $userId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تکمیل شارژ کیف پول',
                'error_code' => 'CHARGE_COMPLETE_ERROR'
            ];
        }
    }

    /**
     * ثبت تراکنش ناموفق
     */
    public function recordFailedCharge(int $userId, float $amount, string $referenceId, ?string $linkingUrl = null): array
    {
        try {
            // پیدا کردن تراکنش pending
            $pendingTransaction = $this->walletRepo->findLatestPendingTransaction();

            if ($pendingTransaction && $pendingTransaction->user_id == $userId) {
                // به‌روزرسانی تراکنش pending به ناموفق
                $this->walletRepo->updateTransactionStatus(
                    $pendingTransaction->id,
                    -200, // ناموفق
                    $referenceId
                );

                Log::warning('شارژ کیف پول ناموفق', [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'reference_id' => $referenceId,
                    'transaction_id' => $pendingTransaction->id
                ]);
            } else {
                // اگر تراکنش pending پیدا نشد، یک تراکنش ناموفق جدید ثبت کن
                $transaction = $this->walletRepo->createTransaction([
                    'user_id' => $userId,
                    'price' => $amount,
                    'referenceId' => $referenceId,
                    'type' => 1, // شارژ کیف پول
                    'status' => -200, // ناموفق
                    'description' => 'شارژ ناموفق کیف پول',
                    'linking_url' => $linkingUrl,
                ]);

                Log::warning('شارژ کیف پول ناموفق (تراکنش جدید)', [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'reference_id' => $referenceId,
                    'transaction_id' => $transaction->id
                ]);
            }

            return [
                'success' => false,
                'message' => 'تراکنش ناموفق بود.',
                'error_code' => 'TRANSACTION_FAILED'
            ];

        } catch (\Exception $e) {
            Log::error('خطا در ثبت تراکنش ناموفق', [
                'user_id' => $userId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت تراکنش',
                'error_code' => 'RECORD_ERROR'
            ];
        }
    }

    /**
     * دریافت موجودی کیف پول
     */
    public function getBalance(int $userId): array
    {
        try {
            $balance = $this->walletRepo->getWalletBalance($userId);

            if ($balance === null) {
                return [
                    'success' => false,
                    'message' => 'کاربر یافت نشد.',
                    'error_code' => 'USER_NOT_FOUND'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'wallet_balance' => $balance
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در دریافت موجودی کیف پول', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت موجودی',
                'error_code' => 'GET_BALANCE_ERROR'
            ];
        }
    }

    /**
     * دریافت تاریخچه تراکنش‌ها
     */
    public function getTransactionHistory(int $userId, int $perPage = 20, ?string $fromDate = null, ?string $toDate = null): array
    {
        try {
            $transactions = $this->walletRepo->getUserTransactions($userId, $perPage, $fromDate, $toDate);

            return [
                'success' => true,
                'data' => [
                    'transactions' => $transactions,
                    'pagination' => [

                    ],
                    'filters' => [
                        'from_date' => $fromDate,
                        'to_date' => $toDate,
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در دریافت تاریخچه تراکنش‌ها', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت تاریخچه',
                'error_code' => 'GET_HISTORY_ERROR'
            ];
        }
    }

    public function completeSuccessfulChargeWithId(int $transactionId, string $referenceId): array
    {
        try {
            DB::beginTransaction();

            $transaction = $this->walletRepo->findTransactionById($transactionId);

            if (!$transaction || $transaction->status != 0) {
                DB::rollBack();
                return ['success' => false, 'message' => 'تراکنش نامعتبر است.'];
            }

            // افزایش موجودی
            $this->walletRepo->increaseWallet($transaction->user_id, (float) $transaction->price);

            // به‌روزرسانی تراکنش به موفق (100)
            $this->walletRepo->updateTransactionStatus($transaction->id, 100, $referenceId);

            DB::commit();

            return [
                'success' => true,
                'message' => 'کیف پول شارژ شد.',
                'data' => ['wallet_balance' => $this->walletRepo->getWalletBalance($transaction->user_id)]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'خطا در دیتابیس.'];
        }
    }
}
