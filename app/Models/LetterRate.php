<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterRate extends Model
{
    protected $fillable = [
        'letter_rate_category_id',
        'type',
        'title',
        'amount',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(LetterRateCategory::class, 'letter_rate_category_id');
    }
}
