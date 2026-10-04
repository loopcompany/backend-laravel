<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * رویداد امنیتی حساب («فعالیت‌های اخیر حساب»).
 */
class AccountActivity extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = [
        'login_success' => 'ورود موفق',
        'login_failed' => 'ورود ناموفق',
        'two_factor_enabled' => 'فعال شدن تأیید دومرحله‌ای',
        'two_factor_disabled' => 'غیرفعال شدن تأیید دومرحله‌ای',
        'mobile_changed' => 'تغییر شماره موبایل',
        'device_logout' => 'خروج از دستگاه',
        'logout_other_devices' => 'خروج از سایر دستگاه‌ها',
        'password_changed' => 'تغییر رمز عبور',
        'security_info_changed' => 'تغییر اطلاعات امنیتی',
        'session_ended' => 'پایان نشست',
        'deletion_requested' => 'درخواست حذف حساب',
    ];

    protected $fillable = [
        'account_type', 'account_id', 'type', 'title', 'result', 'device_id', 'device', 'os',
        'ip', 'approx_location', 'user_agent', 'meta', 'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function account(): MorphTo
    {
        return $this->morphTo();
    }
}
