<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * کاربر مجاز سازمان. فعلاً فقط فهرست مدیریت می‌شود؛ ورود این کاربران تصمیم بعدی است.
 */
class OrganizationUser extends Model
{
    protected $fillable = ['organization_id', 'full_name', 'mobile', 'role'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'mobile' => $this->mobile,
            'role' => $this->role,
        ];
    }
}
