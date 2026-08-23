<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'amount',
        'type',
        'description',
        'sponsor',
        'month',
        'urgent_description',
        'status',
        'response'
    ];

    /**
     * Get the technician that owns the debt request.
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
