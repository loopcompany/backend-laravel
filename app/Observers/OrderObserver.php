<?php

namespace App\Observers;

use App\Helpers\Helper;
use App\Models\Order;
use App\Models\WorkingTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;

class OrderObserver
{
    /**
     * Handle the Order "updating" event.
     * این متد قبل از ذخیره تغییرات اجرا می‌شود
     */
    public function updating(Order $order): void
    {
        // بررسی اینکه آیا date یا time تغییر کرده است
        if ($order->isDirty('date') || $order->isDirty('time')) {
            // اگر is_time_changed قبلاً 1 نبوده، آن را 1 می‌کنیم
            if ($order->is_time_changed != 1) {
                $order->is_time_changed = 1;

                Log::info('زمان سفارش تغییر کرد', [
                    'order_id' => $order->id,
                    'old_date' => $order->getOriginal('date'),
                    'new_date' => $order->date,
                    'old_time' => $order->getOriginal('time'),
                    'new_time' => $order->time,
                ]);
            }
        }
    }

    /**
     * Handle the Order "updated" event.
     * این متد بعد از ذخیره تغییرات اجرا می‌شود
     */
    public function updated(Order $order): void
    {
        $smsService = app(\App\Services\SmsService::class);

        // بررسی لغو سفارش توسط ادمین (status = 5)
        if ($order->isDirty('status') && $order->status == 5) {
            Log::info('سفارش توسط ادمین لغو شد', [
                'order_id' => $order->id,
                'old_status' => $order->getOriginal('status'),
                'new_status' => $order->status,
            ]);

            // ارسال پیامک به کاربر
            if ($order->user && $order->user->phone) {
                $smsService->sendOrderCancelledByAdminToUser($order->user->phone, $order->id);

                Log::info('پیامک لغو سفارش توسط ادمین به کاربر ارسال شد', [
                    'order_id' => $order->id,
                    'user_phone' => $order->user->phone
                ]);
            }

            // ارسال پیامک به تکنسین (در صورت وجود)
            if ($order->technician_id && $order->technician && $order->technician->phone) {
                $smsService->sendOrderCancelledByAdminToTechnician($order->technician->phone, $order->id);

                Log::info('پیامک لغو سفارش توسط ادمین به تکنسین ارسال شد', [
                    'order_id' => $order->id,
                    'technician_phone' => $order->technician->phone
                ]);
            }
        }

        // بررسی اختصاص تکنسین به سفارش توسط ادمین
        if ($order->isDirty('technician_id') && $order->technician_id && $order->getOriginal('technician_id') === null) {
            Log::info('سفارش به تکنسین اختصاص داده شد', [
                'order_id' => $order->id,
                'technician_id' => $order->technician_id
            ]);

            // ارسال پیامک به تکنسین
            if ($order->technician && $order->technician->phone) {
                $smsService->sendOrderAssignedByAdminToTechnician($order->technician->phone, $order->id);

                Log::info('پیامک اختصاص سفارش به تکنسین ارسال شد', [
                    'order_id' => $order->id,
                    'technician_phone' => $order->technician->phone
                ]);
            }
        }

        // بررسی تغییر emergency_help توسط تکنسین/ادمین
        if ($order->isDirty('emergency_help')) {
            Log::info('درخواست کمک اضطراری تغییر کرد', [
                'order_id' => $order->id,
                'emergency_help' => $order->emergency_help
            ]);

            // ارسال پیامک به ادمین (از جدول contacts)
            $adminContact = \App\Models\Contact::where('type', 'sms')->first();
            if ($adminContact && $adminContact->link) {
                $smsService->sendUrgentRequestByTechnicianToAdmin($adminContact->link, $order->id);

                Log::info('پیامک درخواست کمک اضطراری به ادمین ارسال شد', [
                    'order_id' => $order->id,
                    'admin_phone' => $adminContact->link
                ]);
            }
        }

        // بررسی تغییر زمان یا توضیحات تکنسین
        if (
            ($order->wasChanged('date') || $order->wasChanged('time') || $order->wasChanged('technician_des'))
            && $order->user && $order->user->phone
        ) {

            Log::info('تغییر زمان یا توضیحات توسط تکنسین', [
                'order_id' => $order->id,
                'date_changed' => $order->wasChanged('date'),
                'time_changed' => $order->wasChanged('time'),
                'description_changed' => $order->wasChanged('technician_des')
            ]);

            // ارسال پیامک به کاربر
            $smsService->sendTimeChangeOrDescriptionByTechnicianToUser($order->user->phone, $order->id);

            Log::info('پیامک تغییر زمان/توضیحات به کاربر ارسال شد', [
                'order_id' => $order->id,
                'user_phone' => $order->user->phone
            ]);
        }
    }

    public function created(Order $order): void
    {
        $user = $order->user;

        if (!$user || !$user->phone) {
            return;
        }

        $createdAt = $order->created_at ?: now();

        $isInWorkingTime = $this->isInWorkingTime($createdAt);

        if (!$isInWorkingTime) {
            $createdAtJalali = Jalalian::fromCarbon($createdAt)->format('Y/m/d H:i:s');

            Helper::send_sms(
                $user->phone,
                '336619',
                ['createdAt'],
                [$createdAtJalali]
            );
        }
    }

    private function isInWorkingTime(Carbon $dateTime): bool
    {
        $currentTime = $dateTime->format('H:i:s');

        $workingTimes = WorkingTime::query()->get();

        foreach ($workingTimes as $workingTime) {
            $startAt = $workingTime->start_at;
            $endAt = $workingTime->end_at;

            /**
             * حالت معمولی:
             * مثلا 09:00 تا 18:00
             */
            if ($startAt <= $endAt) {
                if ($currentTime >= $startAt && $currentTime <= $endAt) {
                    return true;
                }
            }

            /**
             * حالت شیفت شب:
             * مثلا 22:00 تا 02:00
             */
            if ($startAt > $endAt) {
                if ($currentTime >= $startAt || $currentTime <= $endAt) {
                    return true;
                }
            }
        }

        return false;
    }
}
