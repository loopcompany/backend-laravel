<?php

namespace App\Observers;

use App\Models\User;
use App\Helpers\UserCodeHelper;

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
        //
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
