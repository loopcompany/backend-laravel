<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryFieldConditional extends Model
{

    protected $fillable = [
        'category_field_id',
        'step',
        'sort',
        'type',
        'field_id',
        'field_detail_id'
    ];
    public function CategoryField():BelongsTo
    {
        return $this->belongsTo(CategoryField::class);
    }

    public function field_detail():BelongsTo
    {
        return $this->belongsTo(FieldDetail::class)->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function field():BelongsTo
    {
        return $this->belongsTo(Field::class)->withoutGlobalScope(SoftDeletingScope::class);
    }
    
    public function relatedFields(): HasMany
    {
        return $this->hasMany(CategoryFieldConditional::class, 'step', 'step')
        ->where('field_detail_id', $this->field_detail_id); ; // یا وابسته به دسته‌بندی
    }
}
