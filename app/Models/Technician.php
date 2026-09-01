<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Technician extends Authenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens;

    // وضعیت‌های تایید
    const APPROVAL_PENDING = 'pending';
    const APPROVAL_APPROVED = 'approved';
    const APPROVAL_REJECTED = 'rejected';

    protected $fillable = [
        // اطلاعات شخصی (طبق migration)
        'name',
        'melicode',
        'phone',
        'birth_date',
        'father_name',
        'issued_from',
        'serial_number',
        'marital_status',
        'military_status',
        'education_status',
        'education_field',
        'telephone',
        'mobile',
        'licence_date',
        'vehicle_type',
        'home_postal_code',
        'region',
        'city',
        'home_address',
        
        // اطلاعات احراز هویت
        'phone_verified_at',
        'phone_verify_code',
        'email',
        'email_verified_at',
        'profile_photo_path',
        
        // کدهای پرسنلی
        'referral_code',
        'other_referral_code',
        'password',
        'has_access',
        
        // فیلدهای تایید/رد
        'approval_status',
        'rejection_reason',
        'approved_at',
        'approved_by',
        // مالی
        'commission',
        'wallet',
        
        // مهارت‌ها و توانایی‌ها
        'idea',
        'software_skill',
        'hardware_skill',
        'software_weakness',
        'hardware_weakness',
        'resume',
        'technician_type',
        'certificate_number',
        'certificate_issue_date',
        'certificate_expiry_date',
        'car_model',
        'car_color',
        'car_plate',
        'car_year',
        'car_fuel_type',
        'car_vin',
        'car_insurance_code',
        'car_insurance_expiry_date',
        'bank_shaba_number',
        'bank_name',
        'bank_card_number',
        'limit_access_reason',
        'is_online',
        'is_leave',
        'is_absent',
        'at_work'

    ];

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'has_access' => 'boolean',
        'wallet' => 'decimal:2',
        'commission' => 'integer',
    ];

    protected $hidden = [
        'password',
        'phone_verify_code'
    ];

    // Accessors
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getAgeAttribute(): int
    {
        return $this->birth_date->age ?? 0;
    }

    public function getFormattedRatingAttribute(): string
    {
        return number_format((float)$this->rating, 1) . '/5';
    }

    public function getIsVerifiedAttribute(): bool
    {
        return !is_null($this->verified_at);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'در انتظار تأیید',
            'active' => 'فعال',
            'inactive' => 'غیرفعال',
            'suspended' => 'مسدود شده',
            default => 'نامشخص'
        };
    }

    public function getEducationLevelLabelAttribute(): string
    {
        return match($this->education_level) {
            'diploma' => 'دیپلم',
            'associate' => 'کاردانی',
            'bachelor' => 'کارشناسی',
            'master' => 'کارشناسی ارشد',
            'phd' => 'دکتری',
            default => 'نامشخص'
        };
    }

    public function getGenderLabelAttribute(): string
    {
        return $this->gender == 'male' ? 'مرد' : 'زن';
    }

    public function getMaritalStatusLabelAttribute(): string
    {
        return $this->marital_status == 'married' ? 'متأهل' : 'مجرد';
    }

    // Relations
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'technician_categories');
    }

    public function expertises(): BelongsToMany
    {
        return $this->belongsToMany(Expertise::class, 'technician_expertises');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    public function technician_polls(): HasMany
    {
        return $this->hasMany(TechnicianPoll::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TechnicianReview::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TechnicianTransaction::class);
    }

    public function adminReportViolations(): HasMany
    {
        return $this->hasMany(AdminReportViolation::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    public function firebaseDeviceTokens(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(FirebaseDeviceToken::class, 'tokenable');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(TechnicianTicket::class);
    }

    public function technicianReviews(): HasMany
    {
        return $this->hasMany(TechnicianReview::class);
    }

    public function educationRegisterations(): HasMany
    {
        return $this->hasMany(TechnicianEducationRegisteration::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    public function scopeInCity($query, $city)
    {
        return $query->where('city', $city);
    }

    public function scopeWithSpecialty($query, $categoryId)
    {
        return $query->whereJsonContains('specialties', $categoryId);
    }

    public function scopeWithinRadius($query, $latitude, $longitude, $radius = 10)
    {
        // اگر coordinate های تکنسین در دیتابیس ذخیره شده باشد
        // این scope برای جستجوی تکنسین‌ها در شعاع مشخص استفاده می‌شود
        return $query->where('service_radius', '>=', $radius);
    }

    public function scopeTopRated($query, $minRating = 4.0)
    {
        return $query->where('rating', '>=', $minRating);
    }

    // Methods
    public function updateRating()
    {
        // اگر مدل TechnicianReview وجود داشت:
        // $avgRating = $this->reviews()->avg('rating') ?? 0;
        // $totalReviews = $this->reviews()->count();
        
        // فعلاً به صورت دستی:
        $avgRating = $this->rating ?? 0;
        $totalReviews = $this->total_reviews ?? 0;
        
        $this->update([
            'rating' => round((float)$avgRating, 2),
            'total_reviews' => $totalReviews
        ]);
    }

    public function incrementCompletedJobs()
    {
        $this->increment('completed_jobs');
    }

    public function canAcceptOrder(): bool
    {
        return $this->status == 'active' && $this->is_verified;
    }

    public function hasSpecialty($categoryId): bool
    {
        return in_array($categoryId, $this->specialties ?? []);
    }

    // متودهای مربوط به تایید
    public function approve($adminId = null): bool
    {
        return $this->update([
            'approval_status' => self::APPROVAL_APPROVED,
            'approved_at' => now(),
            'approved_by' => $adminId,
            'rejection_reason' => null
        ]);
    }

    public function reject(string $reason, $adminId = null): bool
    {
        return $this->update([
            'approval_status' => self::APPROVAL_REJECTED,
            'rejection_reason' => $reason,
            'approved_at' => null,
            'approved_by' => $adminId
        ]);
    }

    public function isPending(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->approval_status === self::APPROVAL_REJECTED;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('approval_status', self::APPROVAL_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('approval_status', self::APPROVAL_REJECTED);
    }

    // متود برای گرفتن وضعیت تایید به فارسی
    public function getApprovalStatusLabelAttribute(): string
    {
        return match ($this->approval_status) {
            self::APPROVAL_PENDING => 'در انتظار تایید',
            self::APPROVAL_APPROVED => 'تایید شده',
            self::APPROVAL_REJECTED => 'رد شده',
            default => 'نامشخص'
        };
    }
}
