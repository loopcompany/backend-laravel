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
        'business_name',
        'registration_number',
        'economic_code',
        'suspended_at',
        'suspension_reason',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'profile_approved_at' => 'datetime',
        'contract_approved_at' => 'datetime',
        'suspended_at' => 'datetime',
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

    public function documents(): HasMany
    {
        return $this->hasMany(OrganizationDocument::class);
    }

    public function authorizedUsers(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    /**
     * دریافت آخرین قرارداد
     */
    public function latestContract()
    {
        return $this->hasOne(OrganizationContract::class)->latestOfMany('uploaded_at');
    }

    public const VERIFICATION_STATUSES = [
        'pending' => 'در حال بررسی توسط پنل مدیریت',
        'approved' => 'تأیید شده',
        'suspended' => 'معلق',
        'deleted' => 'حذف شده',
        'rejected' => 'رد شده',
    ];

    /**
     * وضعیت احراز حساب برای اپ: pending | approved | suspended | deleted | rejected
     * حذف (اجرای درخواست حذف حساب) و تعلیق بر وضعیت تأیید پروفایل اولویت دارند.
     */
    public function verificationStatus(): string
    {
        $isDeleted = AccountDeletionRequest::where('user_id', $this->user_id)
            ->where('status', AccountDeletionRequest::STATUS_DONE)
            ->exists();

        if ($isDeleted) {
            return 'deleted';
        }

        if ($this->suspended_at !== null) {
            return 'suspended';
        }

        return in_array($this->profile_status, ['pending', 'approved', 'rejected'], true)
            ? $this->profile_status
            : 'pending';
    }

    public function verificationReason(): ?string
    {
        return match ($this->verificationStatus()) {
            'suspended' => $this->suspension_reason,
            'rejected' => $this->profile_rejection_reason,
            default => null,
        };
    }

    /**
     * چک کردن دسترسی کامل سازمان
     * سازمان زمانی دسترسی کامل دارد که هم پروفایل و هم قرارداد تایید شده باشد
     */
    public function hasCompleteAccess(): bool
    {
        return $this->profile_status === 'approved' &&
               $this->contract_status === 'approved' &&
               $this->suspended_at === null;
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
