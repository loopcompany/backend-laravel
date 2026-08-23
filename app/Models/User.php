<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'phone',
        'code',
        'phone_verify_code',
        'email',
        'melicode',
        'other_referral_code',
        'referral_code',
        'password',
        'birth_date',
        'mobile_number',
        'phone_number',
        'postal_code',
        'city',
        'region',
        'province_id',
        'city_id',
        'region_id',
        'home_address',
        'work_address',
        'card_number',
        'sheba_number',
        'profile_photo_path',
        'is_special',
        'wallet',
        'account_type',
        'by_who',
        'is_online'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'phone_verify_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'wallet' => 'integer',
        ];
    }

    /**
     * Check if user's phone is verified
     */
    public function isPhoneVerified(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    /**
     * Check if user has access
     */
    public function hasAccess(): bool
    {
        return $this->has_access == 1;
    }

    /**
     * رابطه با تراکنش‌های gem
     */
    public function gemTransactions()
    {
        return $this->hasMany(GemTransaction::class);
    }

    /**
     * محاسبه مجموع gem های کاربر
     */
    public function getTotalGemsAttribute(): int
    {
        return $this->gemTransactions()->sum('gems');
    }
    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    /**
     * Get user transactions
     */
    public function transactions()
    {
        return $this->hasMany(UserTransaction::class);
    }

    /**
     * Get user tickets
     */
    public function tickets()
    {
        return $this->hasMany(UserTicket::class);
    }

    /**
     * Get user orders
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get user report violations
     */
    public function reportViolations()
    {
        return $this->hasMany(ReportViolation::class);
    }

    /**
     * Get user poll applications
     */
    public function pollApplications()
    {
        return $this->hasMany(PollApplication::class);
    }

    /**
     * Get user technician reviews
     */
    public function technicianReviews()
    {
        return $this->hasMany(TechnicianReview::class);
    }

    /**
     * Get user's province
     */
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Get user's city
     */
    public function userCity()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    /**
     * Get user's region
     */
    public function userRegion()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /**
     * رابطه با Organization (برای سازمان‌ها)
     */
    public function organization()
    {
        return $this->hasOne(Organization::class);
    }

    /**
     * بررسی اینکه آیا کاربر سازمان است
     */
    public function isOrganization(): bool
    {
        return $this->account_type !== 'individual';
    }

    /**
     * بررسی اینکه آیا کاربر شرکت است
     */
    public function isCompany(): bool
    {
        return $this->account_type === 'company';
    }

    /**
     * بررسی اینکه آیا کاربر عادی است
     */
    public function isIndividual(): bool
    {
        return $this->account_type === 'individual';
    }

    /**
     * رابطه با پیام‌های چت
     */
    public function chats()
    {
        return $this->hasMany(Chat::class);
    }
}
