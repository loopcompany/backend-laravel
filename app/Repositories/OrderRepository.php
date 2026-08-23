<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\DiscountUse;
use App\Models\OrderGallery;
use Illuminate\Support\Facades\Log;
class OrderRepository
{
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function find(int $id): ?Order
    {
        return Order::find($id);
    }

    public function createOrderDetail(array $data): OrderDetail
    {
        return OrderDetail::create($data);
    }
    public function createOrderGallery(array $data): OrderGallery
    {
        Log::info(json_encode($data));
        return OrderGallery::create($data);
    }

    public function createDiscountUse(array $data): DiscountUse
    {
        return DiscountUse::create($data);
    }

    public function getUserOrders(int $userId)
    {
        return Order::where('user_id', $userId)
            ->with([
                'category',
                'user_address',
                'technician' => function ($query) {
                    $query->withAvg('reviews as average_rating', 'technician_rate');
                },
                'discountUse.discount_code'
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * دریافت سفارشات کاربر با فیلترهای تاریخ و وضعیت
     */
    public function getUserOrdersWithFilters(
        int $userId,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $status = null,
        ?int $perPage = null
    ) {
        $query = Order::where('user_id', $userId)
            ->with([
                'category',
                'user_address',
                'technician' => function ($query) {
                    $query->withAvg('reviews as average_rating', 'technician_rate');
                },
                'discountUse.discount_code'
            ]);

        // فیلتر بر اساس تاریخ شروع
        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        // فیلتر بر اساس تاریخ پایان
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        // فیلتر بر اساس وضعیت
        if ($status !== null) {
            $query->where('status', $status);
        }

        $query->orderBy('created_at', 'desc');

        // اگر pagination درخواست شده باشد
        if ($perPage !== null) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    public function getOrderWithDetails(int $orderId): ?Order
    {
        return Order::with(['orderDetails', 'category', 'userAddress'])
            ->find($orderId);
    }

    public function getUserOrderDetail(int $userId, int $orderId): ?Order
    {
        return Order::with([
            'details.field',
            'details.fieldDetail',
            'category',
            'technician',
            'user_address',
            'discountUse.discount_code',
            'order_galleries'
        ])
            ->where('user_id', $userId)
            ->where('id', $orderId)
            ->first();
    }

    public function findOrderWithTechnician(int $orderId): ?Order
    {
        return Order::with('technician')->find($orderId);
    }

    public function updateOrder(int $orderId, array $data): bool
    {
        $order = Order::find($orderId);
        if (!$order) {
            return false;
        }
        return $order->update($data);
    }

    public function getUserOrder(int $userId, int $orderId): ?Order
    {
        return Order::where('user_id', $userId)
            ->where('id', $orderId)
            ->first();
    }

    public function getOrderExtraServices(int $orderId)
    {
        $order = Order::with(['extra_services.extra_service'])->find($orderId);
        return $order ? $order->extra_services : collect();
    }

    public function isUserOrderOwner(int $userId, int $orderId): bool
    {
        return Order::where('id', $orderId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * بررسی اینکه آیا سفارش به تکنسین تعلق دارد
     */
    public function isTechnicianOrderOwner(int $technicianId, int $orderId): bool
    {
        return Order::where('id', $orderId)
            ->where('technician_id', $technicianId)
            ->exists();
    }

    /**
     * دریافت جزئیات سفارش برای تکنسین
     */
    public function getTechnicianOrderDetail(int $technicianId, int $orderId): ?Order
    {
        return Order::with([
            'details.field',
            'details.fieldDetail',
            'category',
            'user',
            'user_address',
            'extra_services',
            'order_galleries'
        ])
            ->where('technician_id', $technicianId)
            ->where('id', $orderId)
            ->first();
    }

    /**
     * به‌روزرسانی فیلد send_to_loop
     */
    public function updateSendToLoop(int $orderId): bool
    {
        return Order::where('id', $orderId)
            ->update(['send_to_loop' => now()]);
    }
    public function updateDoneInPlace(int $orderId, string $technician_in_place_description): bool
    {
        return Order::where('id', $orderId)
            ->update(['done_in_place' => now(), 'technician_in_place_description' => $technician_in_place_description]);
    }

    /**
     * پیدا کردن سفارش با گزارش
     */
    public function findWithReport(int $orderId): ?Order
    {
        return Order::with('technician_order_report')->find($orderId);
    }

    /**
     * بررسی اینکه سفارش به لوپ ارسال شده است
     */
    public function isSentToLoop(int $orderId): bool
    {
        return Order::where('id', $orderId)
            ->whereNotNull('send_to_loop')
            ->exists();
    }
    public function isDoneInPlace(int $orderId): bool
    {
        return Order::where('id', $orderId)
            ->whereNotNull('done_in_place')
            ->exists();
    }

    /**
     * به‌روزرسانی اطلاعات لوپ (duration و loop_description)
     */
    public function updateLoopInfo(int $orderId, array $data): bool
    {
        return Order::where('id', $orderId)
            ->update($data);
    }

    /**
     * بررسی اینکه سفارش هنوز شروع نشده است
     */
    public function isNotStarted(int $orderId): bool
    {
        return Order::where('id', $orderId)
            ->whereNull('started_at')
            ->exists();
    }

    /**
     * ثبت توضیحات تکنسین و تاریخ/ساعت دلخواه
     */
    public function setTechnicianDescription(int $orderId, string $description, string $date, string $time, int $technicianPrice): bool
    {
        // دریافت تاریخ و ساعت فعلی سفارش
        $order = Order::find($orderId);
        if (!$order) {
            return false;
        }

        // بررسی تغییر تاریخ یا ساعت
        $isTimeChanged = ($order->date != $date || $order->time != $time) ? 1 : 0;

        // به‌روزرسانی با استفاده از مدل برای فعال کردن Observer و ارسال پیامک
        $order->technician_des = $description;
        $order->date = $date;
        $order->time = $time;
        $order->technician_price = $technicianPrice;
        $order->is_time_changed = $isTimeChanged;

        return $order->save();
    }

    /**
     * بررسی اینکه سفارش کنسل نشده است
     * شرایط کنسلی: 3:user cancel, 4:tech cancel, 5:admin cancel, 6:expire time
     */
    public function isNotCancelled(int $orderId): bool
    {
        return Order::where('id', $orderId)
            ->whereNotIn('status', [3, 4, 5, 6])
            ->exists();
    }

    /**
     * بررسی اینکه user_initial_accept خالی است
     */
    public function isUserInitialAcceptEmpty(int $orderId): bool
    {
        return Order::where('id', $orderId)
            ->whereNull('user_initial_accept')
            ->exists();
    }

    /**
     * ثبت پذیرش اولیه کاربر
     */
    public function setUserInitialAccept(int $orderId): bool
    {
        return Order::where('id', $orderId)
            ->update([
                'user_initial_accept' => now(),
            ]);
    }

    /**
     * بررسی اینکه آیا سفارش قبلاً تصمیم‌گیری شده
     */
    public function hasUserDecision(int $orderId): bool
    {
        return Order::where('id', $orderId)
            ->where(function ($query) {
                $query->whereNotNull('user_accept_date')
                    ->orWhereNotNull('user_cancellation_date')
                    ->orWhere('status', 3);
            })
            ->exists();
    }

    /**
     * ثبت پذیرش سفارش توسط کاربر
     */
    public function acceptOrder(int $orderId, ?string $reason): bool
    {
        return Order::where('id', $orderId)
            ->update([
                'user_accept_date' => now(),
                'user_cancellation_reason' => $reason
            ]);
    }

    /**
     * ثبت رد سفارش توسط کاربر
     */
    public function rejectOrder(int $orderId, ?string $reason): bool
    {
        return Order::where('id', $orderId)
            ->update([
                'status' => 3, // لغو توسط کاربر
                'user_cancellation_reason' => $reason,
                'user_cancellation_date' => now(),
            ]);
    }

    /**
     * ثبت توضیحات پیگیری بازگشت محصول توسط کاربر
     */
    public function setReturnFollowupDescription(int $orderId, string $description): bool
    {
        return Order::where('id', $orderId)
            ->update([
                'user_return_followup_description' => $description,
            ]);
    }

    /**
     * ثبت زمان حرکت تکنسین به سمت آدرس (راه افتادن)
     */
    public function setSetOffAt(int $orderId): bool
    {
        return Order::where('id', $orderId)
            ->update([
                'set_off_at' => now(),
                'status' => 1, // in progress
            ]);
    }

    /**
     * ثبت زمان رسیدن تکنسین به آدرس
     */
    public function setArrivedAt(int $orderId): bool
    {
        return Order::where('id', $orderId)
            ->update([
                'arrived_at' => now(),
            ]);
    }

    /**
     * ثبت توضیحات نهایی کاربر
     */
    public function setUserFinalDescription(int $orderId, string $description): bool
    {
        return Order::where('id', $orderId)
            ->update([
                'user_final_description' => $description,
            ]);
    }
    public function setUserInPlaceDescription(int $orderId, string $description): bool
    {
        return Order::where('id', $orderId)
            ->update([
                'user_in_place_description' => $description,
            ]);
    }

    /**
     * محاسبه تخفیف از discount_uses (با لحاظ درصد و سقف)
     */
    public function calculateDiscountFromUse(Order $order): float
    {
        // ابتدا چک می‌کنیم که آیا discount_use وجود دارد
        if (!$order->relationLoaded('discountUse')) {
            $order->load('discountUse.discount_code');
        }

        $discountUse = $order->discountUse;

        if (!$discountUse || !$discountUse->discount_code) {
            return 0;
        }

        $discountCode = $discountUse->discount_code;

        // مبلغ نهایی (قیمت پایه + خدمات اضافی)
        $basePrice = $order->technician_price ? $order->technician_price : $order->pakar_price ?? 0;
        $totalPrice = $basePrice + ($order->extra_price ?? 0);

        // محاسبه تخفیف بر اساس درصد روی مبلغ نهایی
        $discountAmount = ($totalPrice * $discountCode->discount_percent) / 100;

        // اعمال سقف تخفیف
        if ($discountCode->max_discount_amount && $discountAmount > $discountCode->max_discount_amount) {
            $discountAmount = $discountCode->max_discount_amount;
        }

        return $discountAmount;
    }

    /**
     * محاسبه قیمت کل سفارش (قیمت نهایی که کاربر باید بپردازد)
     */
    public function calculateTotalPrice(Order $order): float
    {
        // قیمت پاکار + قیمت خدمات اضافی - تخفیف
        $basePrice = $order->technician_price ? $order->technician_price : $order->pakar_price ?? 0;
        $extraPrice = $order->extra_price ?? 0;

        // محاسبه تخفیف از discount_uses
        $discountFromUse = $this->calculateDiscountFromUse($order);

        // اگر تخفیف از discount_use بیشتر از discount_price بود، از آن استفاده می‌کنیم
        $discount = max($discountFromUse, $order->discount_price ?? 0);

        return max(0, $basePrice + $extraPrice - $discount);
    }

    /**
     * محاسبه قیمت تکنسین (بدون خدمات اضافی)
     */
    public function calculateTechnicianPrice(Order $order): float
    {
        // فقط قیمت تکنسین بدون خدمات اضافی
        return $order->technician_price ?? 0;
    }

    /**
     * به‌روزرسانی وضعیت پرداخت سفارش
     */
    public function updatePaymentStatus(int $orderId, int $status, string $pay_type): bool
    {
        if ($pay_type == 'prepay') {
            return Order::where('id', $orderId)
                ->update([
                    'prepayment_payment_status' => $status,
                ]);

        } else {

            return Order::where('id', $orderId)
                ->update([
                    'payment_status' => $status,
                ]);
        }
    }

    /**
     * یافتن سفارش با شرط کاربر
     */
    public function findUserOrder(int $userId, int $orderId): ?Order
    {
        return Order::with(['technician', 'user'])
            ->where('user_id', $userId)
            ->where('id', $orderId)
            ->first();
    }

    /**
     * دریافت تعداد سفارشات تمام شده تکنسین
     */
    public function getCompletedOrdersCount(int $technicianId): int
    {
        return Order::where('technician_id', $technicianId)
            ->where('status', 2)
            ->whereNotNull('finished_at')
            ->count();
    }

    /**
     * دریافت لیست خلاصه سفارشات کاربر
     * فقط شامل: created_at, finished_at, referral_code تکنسین, مبلغ پرداخت شده, نام محصول
     */
    public function getUserOrdersSummary(int $userId)
    {
        return Order::where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->with([
                'technician:id,referral_code',
                'technician_order_report:order_id,product_name',
                'category:id,title'
            ])
            ->select('id', 'technician_id', 'created_at', 'finished_at', 'technician_price', 'extra_price', 'discount_price', 'category_id')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * یافتن سفارش با شناسه
     */
    public function findById(int $orderId): ?Order
    {
        return Order::find($orderId);
    }

    /**
     * به‌روزرسانی درخواست کمک اضطراری
     */
    public function updateEmergencyHelp(int $orderId, string $emergencyHelp, $timestamp): bool
    {
        $order = Order::find($orderId);
        if (!$order) {
            return false;
        }

        return $order->update([
            'emergency_help' => $emergencyHelp,
            'emergency_help_at' => $timestamp
        ]);
    }

    /**
     * به‌روزرسانی نظر تکنسین
     */
    public function updateTechnicianOpinion(int $orderId, string $technicianOpinion): bool
    {
        $order = Order::find($orderId);
        if (!$order) {
            return false;
        }

        return $order->update([
            'technician_opinion' => $technicianOpinion
        ]);
    }
}



