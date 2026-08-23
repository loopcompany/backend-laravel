<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Expertise extends Model
{
    protected $fillable = [
        'title',
        'slug',
    ];

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(Technician::class, 'technician_expertises');
    }
}
