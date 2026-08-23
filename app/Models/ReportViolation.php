<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportViolation extends Model
{
    protected $fillable = [
        'user_id',
        'subject',
        'name',
        'date',
        'amount',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
