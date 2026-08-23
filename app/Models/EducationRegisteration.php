<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationRegisteration extends Model
{
    protected $fillable = [
        'user_id',
        'telephone',
        'phone',
        'address',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
