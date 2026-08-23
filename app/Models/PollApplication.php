<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollApplication extends Model
{
    protected $fillable = [
        'user_id',
        'app_rate',
        'tech_rate',
        'support_rate',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
