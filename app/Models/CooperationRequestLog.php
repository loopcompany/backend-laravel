<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CooperationRequestLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTIONS = [
        'submitted' => 'ثبت درخواست',
        'status_changed' => 'تغییر وضعیت',
        'recruitment_updated' => 'به‌روزرسانی فرم گزینش',
        'note' => 'یادداشت',
        'employment_file_created' => 'تشکیل پرونده استخدامی',
    ];

    protected $fillable = [
        'cooperation_request_id', 'admin_id', 'action', 'from_status', 'to_status', 'result', 'note', 'attachments', 'created_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'created_at' => 'datetime',
    ];

    public function cooperationRequest(): BelongsTo
    {
        return $this->belongsTo(CooperationRequest::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
