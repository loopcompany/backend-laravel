<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianEducationRegisteration extends Model
{
    protected $fillable = [
        'technician_id',
        'telephone',
        'phone',
        'address',
        'description',
    ];

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
}
