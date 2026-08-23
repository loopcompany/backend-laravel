<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insurance extends Model
{
    protected $fillable = [
        'technician_name',
        'technician_code',
        'technician_melicode',
        'history_start_at',
        'history_end_at',
        'duration',
        'loop_start_at',
        'loop_end_at',
        'loop_duration',
    ];

}
