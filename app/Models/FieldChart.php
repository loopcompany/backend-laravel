<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FieldChart extends Model
{
    protected $fillable =[
        'field_detail_id',
        'title',
        'columns_count',
        'first_column',
        'second_column',
        'third_column',
    ];

    public function field_detail(): BelongsTo
    {
        return $this->belongsTo(FieldDetail::class)->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function chart_options(): HasMany
    {
        return $this->hasMany(ChartOption::class);
    }
}
