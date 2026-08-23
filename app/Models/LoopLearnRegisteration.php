<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoopLearnRegisteration extends Model
{
    protected $fillable = [
        'class',
        'register_as',
        'mastery_soft_level',
        'mastery_hard_level',
        'goal',
        'name',
        'lname',
        'birth_date',
        'marriage',
        'gender',
        'nationality',
        'education',
        'phone',
        'telephone',
        'address',
        'vehicle',
        'certificate',
    ];
}
