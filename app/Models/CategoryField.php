<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\EloquentSortable\SortableTrait;

class CategoryField extends Model
{
    use SortableTrait;
    protected $fillable = [
        'step',
        'sort',
        'category_id',
        'field_detail_id',
        'field_id',
        'type',
        'is_conditional',
        'category_field_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class)->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function relatedFields(): HasMany
    {
        return $this->hasMany(CategoryField::class, 'step', 'step'); // یا وابسته به دسته‌بندی
    }

    public function category_field_conditionals(): HasMany
    {
        return $this->hasMany(CategoryFieldConditional::class);
    }
}
