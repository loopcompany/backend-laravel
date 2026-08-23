<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferRequest extends Model
{
    protected $fillable = [
        'technician_id',
        'type',
        'description',
        'status',
        'response'
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    /**
     * Get the technician that owns the transfer request.
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
