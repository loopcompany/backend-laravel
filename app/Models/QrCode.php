<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class QrCode extends Model
{
    protected $fillable = [
        'title',
        'content',
        'type',
        'size',
        'format',
        'file_path',
    ];

    protected $casts = [
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the full URL of the QR code image
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->file_path) {
            return Storage::url($this->file_path);
        }
        return null;
    }

    /**
     * Delete QR code file when model is deleted
     */
    protected static function booted(): void
    {
        static::deleting(function ($qrCode) {
            if ($qrCode->file_path) {
                Storage::disk('public')->delete($qrCode->file_path);
            }
        });
    }
}
