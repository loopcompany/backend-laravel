<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GemAction extends Model
{
    protected $fillable = [
        'name',
        'action_key',
        'gems',
        'is_active',
    ];
}
