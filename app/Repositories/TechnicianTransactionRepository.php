<?php

namespace App\Repositories;

use App\Models\TechnicianTransaction;

class TechnicianTransactionRepository
{
    /**
     * ثبت تراکنش تکنسین
     */
    public function createTransaction(array $data): TechnicianTransaction
    {
        return TechnicianTransaction::create($data);
    }

    /**
     * دریافت تراکنش‌های یک تکنسین
     */
    public function getTechnicianTransactions(int $technicianId, int $perPage = 20)
    {
        return TechnicianTransaction::where('technician_id', $technicianId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * پیدا کردن تراکنش با شماره پیگیری
     */
    public function findTransactionByReferenceId(string $referenceId): ?TechnicianTransaction
    {
        return TechnicianTransaction::where('referenceId', $referenceId)->first();
    }

    /**
     * پیدا کردن تراکنش با شناسه
     */
    public function findTransactionById(int $transactionId): ?TechnicianTransaction
    {
        return TechnicianTransaction::find($transactionId);
    }

    /**
     * دریافت تراکنش‌های مربوط به یک سفارش
     */
    public function getOrderTransactions(int $orderId)
    {
        return TechnicianTransaction::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * به‌روزرسانی وضعیت تراکنش
     */
    public function updateTransactionStatus(int $transactionId, int $status, ?string $referenceId = null): bool
    {
        $transaction = TechnicianTransaction::find($transactionId);
        
        if (!$transaction) {
            return false;
        }

        $transaction->status = $status;
        
        if ($referenceId) {
            $transaction->referenceId = $referenceId;
        }

        return $transaction->save();
    }
}
