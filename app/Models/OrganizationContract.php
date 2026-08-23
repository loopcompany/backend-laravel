<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * قرارداد سازمان
 * 
 * @property int $id
 * @property int $organization_id
 * @property string $contract_file_path
 * @property string $status
 * @property string|null $rejection_reason
 * @property \Carbon\Carbon $uploaded_at
 * @property \Carbon\Carbon|null $reviewed_at
 * @property int|null $reviewed_by
 */
class OrganizationContract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'contract_file_path',
        'status',
        'rejection_reason',
        'uploaded_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'organization_id' => 'integer',
        'uploaded_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'reviewed_by' => 'integer',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * رابطه با سازمان
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * دریافت URL کامل فایل PDF
     */
    public function getContractUrlAttribute(): string
    {
        return asset('storage/' . $this->contract_file_path);
    }

    /**
     * بررسی وضعیت pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * بررسی وضعیت approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * بررسی وضعیت rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * آیا قابل ویرایش است؟ (pending یا rejected)
     */
    public function canBeEdited(): bool
    {
        return $this->isPending() || $this->isRejected();
    }

    /**
     * Scope برای قراردادهای pending
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope برای قراردادهای approved
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope برای قراردادهای rejected
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope برای دریافت آخرین قرارداد یک سازمان
     */
    public function scopeLatestForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId)
            ->orderBy('uploaded_at', 'desc')
            ->orderBy('id', 'desc');
    }
}
