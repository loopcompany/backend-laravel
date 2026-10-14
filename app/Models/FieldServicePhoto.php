<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class FieldServicePhoto extends Model
{
    protected $fillable = ['field_service_visit_id', 'path', 'kind', 'original_name'];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(FieldServiceVisit::class, 'field_service_visit_id');
    }

    public function temporaryUrl(): string
    {
        return url(URL::temporarySignedRoute('api.field-service.photos.show', now()->addMinutes(15), [
            'photo' => $this->getKey(),
        ], absolute: false));
    }

    public function getUrlAttribute(): string
    {
        return $this->temporaryUrl();
    }
}
