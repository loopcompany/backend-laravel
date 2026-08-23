<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DiscountCode extends Model
{

    use SoftDeletes;
    protected $fillable = [
        'club_id',
        'user_id',
        'discount_percent',
        'count',
        'code',
        'expiry_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function club()
    {
        return $this->belongsTo(Club::class)->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function discount_use()
    {
        return $this->hasMany(DiscountUse::class);
    }
}
