<?php

namespace App\Services;

use App\Repositories\WalletRepository;
use App\Support\ProjectLogger;
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

            ProjectLogger::write(ProjectLogger::PAYMENTS, 'info', 'wallet.charge.initiated', [
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
            ProjectLogger::write(ProjectLogger::PAYMENTS, 'error', 'wallet.charge.initiate.failed', [
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
                ProjectLogger::write(ProjectLogger::PAYMENTS, 'warning', 'wallet.charge.pending_transaction_missing', [
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

            ProjectLogger::write(ProjectLogger::PAYMENTS, 'info', 'wallet.charge.completed', [
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

            ProjectLogger::write(ProjectLogger::PAYMENTS, 'error', 'wallet.charge.complete.failed', [
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
    public function recordFailedCharge(int $transactionId, string $trackId): void
    {
        if ($this->walletRepo->failPendingCharge($transactionId, $trackId)) {
            ProjectLogger::write(ProjectLogger::PAYMENTS, 'warning', 'wallet.charge.failed', [
                'transaction_id' => $transactionId,
                'reference_id' => $trackId,
            ]);
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
            ProjectLogger::write(ProjectLogger::PAYMENTS, 'error', 'wallet.balance.retrieve.failed', [
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
            ProjectLogger::write(ProjectLogger::PAYMENTS, 'error', 'wallet.transactions.retrieve.failed', [
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

            $transaction = $this->walletRepo->findTransactionById($transactionId, lock: true);

            if (!$transaction || (int) $transaction->type !== 1 || (int) $transaction->status !== 0) {
                DB::rollBack();
                return ['success' => false, 'message' => 'تراکنش نامعتبر است.'];
            }

            // افزایش موجودی
            if (!$this->walletRepo->increaseWallet($transaction->user_id, (float) $transaction->price)
                || !$this->walletRepo->updateTransactionStatus($transaction->id, 100, $referenceId)) {
                DB::rollBack();
                return ['success' => false, 'message' => 'خطا در ثبت شارژ کیف پول.'];
            }

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
