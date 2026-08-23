<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapRadius extends Model
{
    protected $fillable = [
        'radius',
        'latitude',
        'longitude',
    ];
}
