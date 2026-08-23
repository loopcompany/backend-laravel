<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAuthLog extends Model
{
    protected $fillable = [
        'admin_id',
        'event',
        'ip',
        'user_agent',
        'logged_at',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
