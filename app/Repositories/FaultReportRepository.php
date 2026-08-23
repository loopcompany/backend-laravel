<?php

namespace App\Repositories;

use App\Models\FaultReport;
use Illuminate\Database\Eloquent\Collection;

class FaultReportRepository
{
    /**
     * ایجاد گزارش خرابی جدید
     */
    public function create(array $data): FaultReport
    {
        return FaultReport::create($data);
    }

    /**
     * دریافت گزارش‌های خرابی یک کاربر
     */
    public function getUserFaultReports(int $userId)
    {
        return FaultReport::where('user_id', $userId)
            ->with(['order', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * دریافت یک گزارش خرابی بر اساس ID
     */
    public function findById(int $id): ?FaultReport
    {
        return FaultReport::with(['order', 'user'])->find($id);
    }

    /**
     * دریافت گزارش‌های خرابی مربوط به یک سفارش
     */
    public function getFaultReportsByOrder(int $orderId): Collection
    {
        return FaultReport::where('order_id', $orderId)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
