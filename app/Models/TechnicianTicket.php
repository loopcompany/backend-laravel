<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianTicket extends Model
{
    protected $fillable = [
        'technician_id',
        'message',
        'role',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Check if message is read
     */
    public function isRead(): bool
    {
        return $this->is_read === 1;
    }

    /**
     * Mark as read
     */
    public function markAsRead(): void
    {
        $this->update(['is_read' => 1]);
    }

    /**
     * Get role label in Persian
     */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'technician' => 'Technician',
            'admin' => 'Admin',
            default => 'Unknown',
        };
    }

    /**
     * Relationship with Technician
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
