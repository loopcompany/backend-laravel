<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditRequest extends Model
{
    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;

    protected $fillable = [
        'user_id',
        'organization_id',
        'status',

        'profile_image',
        'email',
        'mobile_number',
        'birth_date',
        'city',
        'region',
        'postal_code',
        'province_id',
        'city_id',
        'region_id',

        'organization_name',
        'organization_code',
        'organization_phone',
        'organization_address',
        'manager_full_name',
        'manager_national_code',
        'agent_name',
        'agent_phone',
        'history',
        'business_name',
    ];

    protected $casts = [
        'status' => 'integer',
        'province_id' => 'integer',
        'city_id' => 'integer',
        'region_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}