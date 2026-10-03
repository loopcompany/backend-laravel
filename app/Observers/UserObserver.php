<?php

namespace App\Observers;

use App\Models\User;
use App\Helpers\UserCodeHelper;
use App\Services\Security\AccountSecurityLogger;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     * تولید خودکار کد کاربر هنگام ایجاد
     */
    public function creating(User $user): void
    {
        // اگر کد از قبل تنظیم نشده بود، تولید خودکار
        if (empty($user->code)) {
            // $user->code = UserCodeHelper::generateUserCode($user);
        }
    }

    /**
     * Handle the User "updating" event.
     * اگر province یا region تغییر کرد، کد را دوباره تولید کن
     */
    public function updating(User $user): void
    {
        // فقط اگر province_id یا region_id تغییر کرده باشد
        if ($user->isDirty(['province_id', 'region_id', 'account_type', 'is_special'])) {
            // اگر کاربر از قبل کد دارد، آن را حفظ می‌کنیم
            // این رفتار را می‌توانید تغییر دهید
            
            // برای regenerate کردن کد، uncomment کنید:
            // $user->code = UserCodeHelper::generateUserCode($user);
        }
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // «تغییر رمز عبور» در فعالیت‌ها و هشدارهای امنیتی؛ همه‌ی مسیرها (پروفایل، بازیابی رمز و ...) را پوشش می‌دهد.
        // اولین تنظیم رمز (رمز قبلی خالی) و پاک شدن رمز هنگام حذف حساب، تغییر رمز حساب نمی‌شوند.
        if ($user->wasChanged('password') && $user->getOriginal('password') !== null && $user->password !== null) {
            $logger = app(AccountSecurityLogger::class);
            $logger->activity($user, 'password_changed', request());
            $logger->alert($user, 'password_changed', request());
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
