<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldServiceReview extends Model
{
    protected $fillable = ['field_service_visit_id', 'user_id', 'decision', 'note'];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(FieldServiceVisit::class, 'field_service_visit_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
