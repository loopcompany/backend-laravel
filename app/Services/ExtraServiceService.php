<?php

namespace App\Services;

use App\Repositories\ExtraServiceRepository;
use Illuminate\Support\Collection;

class ExtraServiceService
{
    public function __construct(
        protected ExtraServiceRepository $extraServiceRepository
    ) {}

    /**
     * دریافت خدمات اضافی برای یک دسته‌بندی و سفارش خاص
     *
     * @param int $categoryId
     * @param int $orderId
     * @param int|null $technicianId
     * @return array
     */
    public function getExtraServicesForOrder(int $categoryId, int $orderId, ?int $technicianId = null): array
    {
        // بررسی وجود سفارش
        if (!$this->extraServiceRepository->orderExists($orderId)) {
            return [
                'success' => false,
                'message' => 'سفارش مورد نظر یافت نشد.',
                'error_code' => 'ORDER_NOT_FOUND'
            ];
        }

        // اگر تکنسین ارسال شده، بررسی دسترسی
        if ($technicianId !== null) {
            if (!$this->extraServiceRepository->isTechnicianAuthorizedForOrder($orderId, $technicianId)) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به دسترسی به این سفارش نیستید.',
                    'error_code' => 'UNAUTHORIZED_ACCESS'
                ];
            }
        }

        // دریافت خدمات اضافی بر اساس دسته‌بندی
        $extraServices = $this->extraServiceRepository->getExtraServicesByCategory($categoryId);

        // دریافت خدمات اضافی اختصاص یافته به سفارش
        $orderExtraServices = $this->extraServiceRepository->getOrderExtraServices($orderId);

        // آماده‌سازی داده‌های خدمات اضافی سفارش
        $orderItems = $this->mapOrderExtraServices($orderExtraServices);

        return [
            'success' => true,
            'data' => [
                'extra_services' => $extraServices,
                'order_items' => $orderItems,
            ]
        ];
    }

    /**
     * تبدیل خدمات اضافی سفارش به فرمت مناسب
     *
     * @param Collection $orderExtraServices
     * @return array
     */
    protected function mapOrderExtraServices(Collection $orderExtraServices): array
    {
        return $orderExtraServices->map(function ($item) {
            return [
                'id' => $item->extra_service_id,
                'extra_detail_id' => $item->extra_service_detail_id,
                'price' => $item->price,
                'title' => $item->title,
            ];
        })->toArray();
    }

    /**
     * ذخیره خدمات اضافی برای یک سفارش
     *
     * @param int $orderId
     * @param array|null $extras
     * @param int|null $technicianId
     * @return array
     */
    public function storeExtraServices(int $orderId, ?array $extras = null, ?int $technicianId = null): array
    {
        // بررسی وجود سفارش
        $order = $this->extraServiceRepository->findOrder($orderId);
        
        if (!$order) {
            return [
                'success' => false,
                'message' => 'سفارش مورد نظر یافت نشد.',
                'error_code' => 'ORDER_NOT_FOUND'
            ];
        }

        // بررسی دسترسی تکنسین
        if ($technicianId !== null) {
            if (!$this->extraServiceRepository->isTechnicianAuthorizedForOrder($orderId, $technicianId)) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به تغییر خدمات این سفارش نیستید.',
                    'error_code' => 'UNAUTHORIZED_ACCESS'
                ];
            }
        }

        // حذف خدمات اضافی قبلی
        $this->extraServiceRepository->deleteOrderExtraServices($orderId);

        // اگر لیست extras خالی یا null بود، کاری غیر از حذف نیاز نیست
        $extras = $extras ?? [];

        // ذخیره خدمات جدید (در صورت وجود)
        foreach ($extras as $extraData) {
            $result = $this->processExtraService($orderId, $extraData);

            if (!$result['success']) {
                return $result;
            }
        }

        // به‌روزرسانی قیمت‌ها
        $this->extraServiceRepository->updateOrderExtraPrice($orderId);
        $this->extraServiceRepository->updateOrderDiscountPrice($orderId);

        return [
            'success' => true,
            'message' => 'خدمات اضافی با موفقیت ثبت شد.',
        ];
    }

    /**
     * پردازش و ذخیره یک خدمت اضافی
     *
     * @param int $orderId
     * @param array $extraData
     * @return array
     */
    protected function processExtraService(int $orderId, array $extraData): array
    {
    $extraId = $extraData['id'] ?? null;
    // accept both camelCase (from client) and snake_case keys
    $extraDetailId = $extraData['extraDetailId'] ?? $extraData['extra_detail_id'] ?? null;
    $customPrice = $extraData['price'] ?? null;

        if (!$extraId) {
            return [
                'success' => false,
                'message' => 'شناسه خدمت اضافی الزامی است.',
                'error_code' => 'EXTRA_ID_REQUIRED'
            ];
        }

        // دریافت اطلاعات خدمت اضافی
        $extra = $this->extraServiceRepository->findExtraService($extraId);
        
        if (!$extra) {
            return [
                'success' => false,
                'message' => 'خدمت اضافی یافت نشد.',
                'error_code' => 'EXTRA_NOT_FOUND'
            ];
        }

        $finalPrice = 0;
        $title = null;

        // اگر جزئیات خدمت (مثلاً نوع خاص) مشخص شده
        if ($extraDetailId) {
            $extraDetail = $this->extraServiceRepository->findExtraServiceDetail($extraDetailId);
            
            if (!$extraDetail) {
                return [
                    'success' => false,
                    'message' => 'جزئیات خدمت اضافی یافت نشد.',
                    'error_code' => 'EXTRA_DETAIL_NOT_FOUND'
                ];
            }

            $finalPrice = $extraDetail->price;
            $title = $extraDetail->title;
        } else {
            // استفاده از قیمت پیشنهادی یا قیمت دستی
            $finalPrice = $extra->recommended_price > 0 
                ? $extra->recommended_price 
                : intval($customPrice ?? 0);
            $title = $extra->title;
        }

        // ذخیره خدمت اضافی
        $this->extraServiceRepository->createOrderExtraService([
            'order_id' => $orderId,
            'extra_service_id' => $extraId,
            'extra_service_detail_id' => $extraDetailId,
            'price' => $finalPrice,
            'title' => $title,
        ]);

        return ['success' => true];
    }

    /**
     * نمایش خدمات اضافی یک سفارش
     *
     * @param int $orderId
     * @param int|null $userId
     * @return array
     */
    public function showOrderExtraServices(int $orderId, ?int $userId = null): array
    {
        // بررسی وجود سفارش
        if (!$this->extraServiceRepository->orderExists($orderId)) {
            return [
                'success' => false,
                'message' => 'سفارش مورد نظر یافت نشد.',
                'error_code' => 'ORDER_NOT_FOUND'
            ];
        }

        // بررسی دسترسی کاربر (اگر userId ارسال شده)
        if ($userId !== null) {
            if (!$this->extraServiceRepository->isUserOwnerOfOrder($orderId, $userId)) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به مشاهده خدمات این سفارش نیستید.',
                    'error_code' => 'UNAUTHORIZED_ACCESS'
                ];
            }
        }

        $extraServices = $this->extraServiceRepository->getOrderExtraServices($orderId);

        return [
            'success' => true,
            'data' => $extraServices,
        ];
    }
}
