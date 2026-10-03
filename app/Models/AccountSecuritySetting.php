<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * تنظیمات امنیتی هر حساب: تأیید دومرحله‌ای و اعلان‌های امنیتی.
 */
class AccountSecuritySetting extends Model
{
    protected $fillable = [
        'account_type', 'account_id', 'two_factor_enabled', 'two_factor_method', 'two_factor_secret',
        'two_factor_pending_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
        'security_alerts_enabled',
    ];

    protected $attributes = [
        'two_factor_enabled' => false,
        'security_alerts_enabled' => true,
    ];

    protected $casts = [
        'two_factor_enabled' => 'boolean',
        'security_alerts_enabled' => 'boolean',
        'two_factor_secret' => 'encrypted',
        'two_factor_pending_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'encrypted:array',
        'two_factor_confirmed_at' => 'datetime',
    ];

    protected $hidden = ['two_factor_secret', 'two_factor_pending_secret', 'two_factor_recovery_codes'];

    public function account(): MorphTo
    {
        return $this->morphTo();
    }
}
