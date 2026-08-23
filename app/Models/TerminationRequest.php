<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerminationRequest extends Model
{
    protected $fillable = [
        'technician_id',
        'type',
        'start_date',
        'end_date',
        'description',
        'status',
        'response'
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    /**
     * Get the technician that owns the termination request.
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
