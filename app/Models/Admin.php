<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Filament\Models\Contracts\HasAvatar;
class Admin extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The guard name for this model.
     */
    protected string $guard_name = 'admin';

    // انواع پرسنل (staff_type)
    const STAFF_OFFICE = 'اداری';
    const STAFF_FIELD = 'میدانی';
    const STAFF_MANAGER = 'مدیر';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'personnel_code',
        'phone',
        'staff_type',
        'password',
        'is_active',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * انواع پرسنل قابل انتخاب
     *
     * @return array<string, string>
     */
    public static function staffTypes(): array
    {
        return [
            self::STAFF_OFFICE => 'کارمند اداری',
            self::STAFF_FIELD => 'کارمند میدانی',
            self::STAFF_MANAGER => 'مدیر',
        ];
    }

    /**
     * Check if admin has super admin role
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Scope to get only active admins
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * فیلتر بر اساس نوع پرسنل (اداری | میدانی | مدیر) - تکی یا گروهی
     */
    public function scopeStaffType($query, string|array $types)
    {
        return $query->whereIn('staff_type', (array) $types);
    }
    
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
    
    
    public function getFilamentAvatarUrl(): ?string
    {
        return asset('assets/images/logo.png');
    }
}
