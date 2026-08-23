<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianReview extends Model
{
    protected $fillable = [
        'user_id',
        'technician_id',
        'order_id',
        'application_rate',
        'technician_rate',
        'support_rate',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
