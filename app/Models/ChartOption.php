<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChartOption extends Model
{
    protected $fillable = [
        'field_chart_id',
        'first',
        'second',
        'third',
    ];

    public function field_chart(): BelongsTo
    {
        return $this->belongsTo(FieldChart::class);
    }
}
