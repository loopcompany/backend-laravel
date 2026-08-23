<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationRequest extends Model
{
    protected $fillable = [
        'section',
        'description',
        'technician_id',
        'status',
        'response'
    ];

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
}
