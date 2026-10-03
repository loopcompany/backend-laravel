<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountKnownDevice extends Model
{
    public $timestamps = false;

    protected $fillable = ['account_type', 'account_id', 'device_id', 'first_seen_at', 'last_seen_at'];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];
}
