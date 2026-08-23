<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminReportViolation extends Model
{
    protected $fillable = [
        'technician_id',
        'title',
        'description',
        'technician_response',
        'can_reply',
    ];

    protected $casts = [
        'can_reply' => 'integer',
    ];

    /**
     * گزارش تخلف متعلق به چه تکنسینی است
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }

    /**
     * آیا تکنسین می‌تواند پاسخ دهد؟
     */
    public function canReply(): bool
    {
        return $this->can_reply === 1;
    }

    /**
     * آیا تکنسین پاسخ داده است؟
     */
    public function hasResponse(): bool
    {
        return !empty($this->technician_response);
    }

    /**
     * برچسب وضعیت پاسخ
     */
    public function getResponseStatusLabelAttribute(): string
    {
        if ($this->hasResponse()) {
            return 'پاسخ داده شده';
        }

        if ($this->canReply()) {
            return 'در انتظار پاسخ';
        }

        return 'بدون امکان پاسخ';
    }
}
