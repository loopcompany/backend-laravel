<?php

namespace App\Repositories;

use App\Models\ExtraService;
use App\Models\OrderExtraService;
use Illuminate\Support\Collection;

class ExtraServiceRepository
{
    /**
     * دریافت خدمات اضافی بر اساس دسته‌بندی
     *
     * @param int $categoryId
     * @return Collection
     */
    public function getExtraServicesByCategory(int $categoryId): Collection
    {
        return ExtraService::whereHas('extra_service_categories', function ($query) use ($categoryId) {
            $query->where('category_id', $categoryId);
        })
            ->with(['extra_service_details'])
            ->get();
    }

    /**
     * دریافت خدمات اضافی اختصاص یافته به یک سفارش
     *
     * @param int $orderId
     * @return Collection
     */
    public function getOrderExtraServices(int $orderId): Collection
    {
        return OrderExtraService::where('order_id', $orderId)->get();
    }

    /**
     * بررسی وجود سفارش
     *
     * @param int $orderId
     * @return bool
     */
    public function orderExists(int $orderId): bool
    {
        return \App\Models\Order::where('id', $orderId)->exists();
    }

    /**
     * بررسی اینکه آیا تکنسین مجاز به دسترسی به این سفارش است
     *
     * @param int $orderId
     * @param int $technicianId
     * @return bool
     */
    public function isTechnicianAuthorizedForOrder(int $orderId, int $technicianId): bool
    {
        return \App\Models\Order::where('id', $orderId)
            ->where('technician_id', $technicianId)
            ->exists();
    }

    /**
     * حذف تمام خدمات اضافی یک سفارش
     *
     * @param int $orderId
     * @return void
     */
    public function deleteOrderExtraServices(int $orderId): void
    {
        OrderExtraService::where('order_id', $orderId)->forceDelete();
    }

    /**
     * ایجاد خدمت اضافی جدید برای سفارش
     *
     * @param array $data
     * @return OrderExtraService
     */
    public function createOrderExtraService(array $data): OrderExtraService
    {
        return OrderExtraService::create($data);
    }

    /**
     * دریافت اطلاعات یک خدمت اضافی
     *
     * @param int $extraId
     * @return \App\Models\ExtraService|null
     */
    public function findExtraService(int $extraId): ?\App\Models\ExtraService
    {
        return ExtraService::find($extraId);
    }

    /**
     * دریافت اطلاعات جزئیات یک خدمت اضافی
     *
     * @param int $extra_detail_id
     * @return \App\Models\ExtraServiceDetail|null
     */
    public function findExtraServiceDetail(int $extra_detail_id): ?\App\Models\ExtraServiceDetail
    {
        return \App\Models\ExtraServiceDetail::find($extra_detail_id);
    }

    /**
     * به‌روزرسانی قیمت خدمات اضافی سفارش
     *
     * @param int $orderId
     * @return void
     */
    public function updateOrderExtraPrice(int $orderId): void
    {
        $order = \App\Models\Order::find($orderId);
        
        if ($order) {
            $extraPrice = OrderExtraService::where('order_id', $orderId)->sum('price');
            $order->update(['extra_price' => $extraPrice]);
        }
    }

    /**
     * به‌روزرسانی قیمت تخفیف سفارش
     * این متد باید منطق محاسبه تخفیف را پیاده‌سازی کند
     *
     * @param int $orderId
     * @return void
     */
    public function updateOrderDiscountPrice(int $orderId): void
    {
        $order = \App\Models\Order::find($orderId);
        
        if ($order) {
            // منطق محاسبه تخفیف اینجا قرار می‌گیرد
            // فعلاً فقط به‌عنوان placeholder
            // در صورت نیاز باید با منطق واقعی جایگزین شود
        }
    }

    /**
     * دریافت سفارش
     *
     * @param int $orderId
     * @return \App\Models\Order|null
     */
    public function findOrder(int $orderId): ?\App\Models\Order
    {
        return \App\Models\Order::find($orderId);
    }

    /**
     * بررسی اینکه آیا کاربر مالک سفارش است
     *
     * @param int $orderId
     * @param int $userId
     * @return bool
     */
    public function isUserOwnerOfOrder(int $orderId, int $userId): bool
    {
        return \App\Models\Order::where('id', $orderId)
            ->where('user_id', $userId)
            ->exists();
    }
}
