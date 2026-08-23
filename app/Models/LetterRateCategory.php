<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LetterRateCategory extends Model
{
    protected $fillable = [
        'title',
    ];

    public function letterRates(): HasMany
    {
        return $this->hasMany(LetterRate::class, 'letter_rate_category_id');
    }
}
