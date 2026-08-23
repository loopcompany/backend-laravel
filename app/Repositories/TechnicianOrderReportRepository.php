<?php

namespace App\Repositories;

use App\Models\TechnicianOrderReport;
use Illuminate\Database\Eloquent\Collection;

class TechnicianOrderReportRepository
{
    /**
     * ایجاد گزارش جدید
     */
    public function create(int $technicianId, array $data): TechnicianOrderReport
    {
        return TechnicianOrderReport::create([
            'technician_id' => $technicianId,
            ...$data
        ]);
    }

    /**
     * به‌روزرسانی گزارش
     */
    public function update(int $reportId, array $data): bool
    {
        return TechnicianOrderReport::where('id', $reportId)
            ->update($data);
    }

    /**
     * پیدا کردن گزارش بر اساس ID
     */
    public function find(int $reportId): ?TechnicianOrderReport
    {
        return TechnicianOrderReport::with(['technician', 'order'])
            ->find($reportId);
    }

    /**
     * پیدا کردن گزارش بر اساس سفارش
     */
    public function findByOrder(int $orderId): ?TechnicianOrderReport
    {
        return TechnicianOrderReport::with(['technician', 'order'])
            ->where('order_id', $orderId)
            ->first();
    }

    /**
     * پیدا کردن گزارش‌های یک تکنسین
     */
    public function getTechnicianReports(int $technicianId, bool $confirmedOnly = false): Collection
    {
        $query = TechnicianOrderReport::with(['order'])
            ->where('technician_id', $technicianId);

        if ($confirmedOnly) {
            $query->confirmed();
        }

        return $query->latest()->get();
    }

    /**
     * تایید گزارش توسط کاربر
     */
    public function confirmReport(int $reportId): bool
    {
        return TechnicianOrderReport::where('id', $reportId)
            ->whereNull('user_confirmed_at')
            ->update([
                'user_confirmed_at' => now()
            ]);
    }

    /**
     * بررسی اینکه آیا گزارش برای این سفارش وجود دارد
     */
    public function reportExistsForOrder(int $orderId): bool
    {
        return TechnicianOrderReport::where('order_id', $orderId)->exists();
    }

    /**
     * بررسی مالکیت گزارش
     */
    public function isOwnedByTechnician(int $reportId, int $technicianId): bool
    {
        return TechnicianOrderReport::where('id', $reportId)
            ->where('technician_id', $technicianId)
            ->exists();
    }

    /**
     * بررسی اینکه آیا گزارش به سفارش کاربر تعلق دارد
     */
    public function belongsToUser(int $reportId, int $userId): bool
    {
        return TechnicianOrderReport::whereHas('order', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->where('id', $reportId)->exists();
    }

    /**
     * بررسی اینکه آیا گزارش سفارش تایید شده است
     */
    public function isOrderReportConfirmed(int $orderId): bool
    {
        return TechnicianOrderReport::where('order_id', $orderId)
            ->whereNotNull('user_confirmed_at')
            ->exists();
    }
}
