<?php

namespace App\Repositories;

use App\Models\DeliveryReport;
use Illuminate\Support\Collection;

class DeliveryReportRepository
{
    /**
     * ایجاد گزارش تحویل جدید
     */
    public function create(array $data): DeliveryReport
    {
        return DeliveryReport::create($data);
    }

    /**
     * به‌روزرسانی گزارش تحویل
     */
    public function update(int $id, array $data): bool
    {
        return DeliveryReport::where('id', $id)->update($data);
    }

    /**
     * دریافت گزارش تحویل بر اساس ID
     */
    public function findById(int $id): ?DeliveryReport
    {
        return DeliveryReport::with(['order', 'technician'])->find($id);
    }

    /**
     * دریافت گزارش تحویل بر اساس orderId
     */
    public function findByOrderId(int $orderId): ?DeliveryReport
    {
        return DeliveryReport::with(['order', 'technician'])
            ->where('order_id', $orderId)
            ->first();
    }

    /**
     * دریافت گزارش تحویل برای تکنسین خاص
     */
    public function findByTechnicianAndOrder(int $technicianId, int $orderId): ?DeliveryReport
    {
        return DeliveryReport::with(['order', 'technician'])
            ->where('technician_id', $technicianId)
            ->where('order_id', $orderId)
            ->first();
    }

    /**
     * دریافت تمام گزارش‌های تحویل یک تکنسین
     */
    public function getTechnicianReports(int $technicianId): Collection
    {
        return DeliveryReport::with(['order'])
            ->where('technician_id', $technicianId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * بررسی اینکه آیا گزارش تایید شده توسط کاربر است
     */
    public function isVerifiedByUser(int $id): bool
    {
        return DeliveryReport::where('id', $id)
            ->whereNotNull('user_verified_at')
            ->exists();
    }

    /**
     * تایید گزارش توسط کاربر
     */
    public function verifyByUser(int $id): bool
    {
        return DeliveryReport::where('id', $id)
            ->update(['user_verified_at' => now()]);
    }

    /**
     * دریافت گزارش بر اساس کد تایید
     */
    public function findByVerificationCode(string $hashedCode): ?DeliveryReport
    {
        return DeliveryReport::with(['order', 'technician'])
            ->where('verification_code', $hashedCode)
            ->first();
    }

    /**
     * بررسی اینکه آیا سفارش به تکنسین تعلق دارد
     */
    public function belongsToTechnician(int $id, int $technicianId): bool
    {
        return DeliveryReport::where('id', $id)
            ->where('technician_id', $technicianId)
            ->exists();
    }
}
