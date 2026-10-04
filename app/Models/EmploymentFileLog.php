<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmploymentFileLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['employment_file_id', 'admin_id', 'action', 'changed_fields', 'created_at'];

    protected $casts = [
        'changed_fields' => 'array',
        'created_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
