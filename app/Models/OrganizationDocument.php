<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class OrganizationDocument extends Model
{
    public const STATUSES = [
        'pending' => 'در انتظار بررسی',
        'approved' => 'تأیید شده',
        'rejected' => 'رد شده',
    ];

    /** مدت اعتبار لینک امضاشده‌ی دانلود */
    public const URL_TTL_MINUTES = 30;

    protected $fillable = [
        'organization_id', 'title', 'file_path', 'original_name', 'mime_type', 'size',
        'status', 'rejection_reason', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    /**
     * لینک امضاشده؛ بدون توکن هم باز می‌شود ولی بعد از ۳۰ دقیقه منقضی است.
     * امضا روی مسیر نسبی است تا پشت پروکسی (http/https یا دامنه‌ی متفاوت) معتبر بماند.
     */
    public function signedUrl(): string
    {
        return url(URL::temporarySignedRoute(
            'api.organization.documents.file',
            now()->addMinutes(self::URL_TTL_MINUTES),
            ['document' => $this->id],
            absolute: false
        ));
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title ?: $this->original_name,
            'url' => $this->signedUrl(),
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'status' => $this->status,
            'status_label' => self::STATUSES[$this->status] ?? $this->status,
            'rejection_reason' => $this->status === 'rejected' ? $this->rejection_reason : null,
            'uploaded_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
