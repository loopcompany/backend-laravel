<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchiveImage extends Model
{
    protected $fillable = [
        'technician_id',
        'image_path',
    ];
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
}
