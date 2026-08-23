<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianPoll extends Model
{
    protected $fillable = [
        'technician_id',
        'user_application',
        'technician_application',
        'inner_personnel',
        'field_personnel',
        'other',
    ];

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
}
