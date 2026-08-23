<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'organization_name',
        'organization_code',
        'organization_phone',
        'organization_address',
        'manager_full_name',
        'manager_national_code',
        'profile_image',
        'profile_status',
        'profile_approved_at',
        'profile_rejection_reason',
        'contract_status',
        'contract_approved_at',
        'contract_rejection_reason',
        'agent_name',
        'agent_phone',
        'history',
        'business_name'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'profile_approved_at' => 'datetime',
        'contract_approved_at' => 'datetime',
    ];

    /**
     * رابطه با User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * رابطه با قراردادهای سازمان
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(OrganizationContract::class);
    }

    /**
     * دریافت آخرین قرارداد
     */
    public function latestContract()
    {
        return $this->hasOne(OrganizationContract::class)->latestOfMany('uploaded_at');
    }

    /**
     * چک کردن دسترسی کامل سازمان
     * سازمان زمانی دسترسی کامل دارد که هم پروفایل و هم قرارداد تایید شده باشد
     */
    public function hasCompleteAccess(): bool
    {
        return $this->profile_status === 'approved' && 
               $this->contract_status === 'approved';
    }

    /**
     * Accessor برای has_complete_access
     */
    public function getHasCompleteAccessAttribute(): bool
    {
        return $this->hasCompleteAccess();
    }

    /**
     * چک کردن وضعیت پروفایل
     */
    public function isProfileApproved(): bool
    {
        return $this->profile_status === 'approved';
    }

    public function isProfilePending(): bool
    {
        return $this->profile_status === 'pending';
    }

    public function isProfileRejected(): bool
    {
        return $this->profile_status === 'rejected';
    }

    /**
     * چک کردن وضعیت قرارداد
     */
    public function isContractApproved(): bool
    {
        return $this->contract_status === 'approved';
    }

    public function isContractPending(): bool
    {
        return $this->contract_status === 'pending';
    }

    public function isContractRejected(): bool
    {
        return $this->contract_status === 'rejected';
    }

    public function isContractNotUploaded(): bool
    {
        return $this->contract_status === 'not_uploaded';
    }

    /**
     * دریافت لیبل فارسی وضعیت
     */
    public function getProfileStatusLabelAttribute(): string
    {
        return match($this->profile_status) {
            'approved' => 'تایید شده',
            'rejected' => 'رد شده',
            'pending' => 'در انتظار تایید',
            default => 'نامشخص',
        };
    }

    public function getContractStatusLabelAttribute(): string
    {
        return match($this->contract_status) {
            'approved' => 'تایید شده',
            'rejected' => 'رد شده',
            'pending' => 'در انتظار تایید',
            'not_uploaded' => 'آپلود نشده',
            default => 'نامشخص',
        };
    }
}
