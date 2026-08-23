<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    protected $fillable = [
        'name',
        'num',
        'delivered_at',
        'technician_name',
        'technician_code',
        'technician_melicode',
    ];
}
