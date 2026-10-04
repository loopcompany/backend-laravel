<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * هشدار امنیتی حساب.
 */
class SecurityAlert extends Model
{
    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];

    protected $fillable = [
        'account_type', 'account_id', 'type', 'title', 'message', 'severity',
        'suggested_action', 'device', 'ip', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function account(): MorphTo
    {
        return $this->morphTo();
    }
}
