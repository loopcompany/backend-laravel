<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends Model
{
    protected $fillable = [
        'technician_id',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * تسویه متعلق به چه تکنسینی است
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
