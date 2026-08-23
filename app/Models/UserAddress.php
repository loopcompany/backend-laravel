<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    protected $fillable = [
        'title',
        'fname',
        'lname',
        'telephone',
        'mobile',
        'user_id',
        'city',
        'region',
        'region_id',
        'latitude',
        'longitude',
        'address',
        'unit',
        'number',
        'floor',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->fname . ' ' . $this->lname;
    }
}
