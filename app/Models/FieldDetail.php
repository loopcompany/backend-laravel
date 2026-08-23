<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\SortableTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FieldDetail extends Model
{
    use SoftDeletes;
    use SortableTrait;

    public $sortable = [
        'order_column_name' => 'sort',
        'sort_when_creating' => true,
    ];

    protected $fillable = [
        'field_id',
        'title',
        'second_title',
        'is_required',
        'image_path',
        'price',
        'show_price',
        'is_checked',
        'has_counter',
        'min',
        'max',
        'icon_name',
        'guide',
        'des',
        'sort',
        'image_path',
        'precentage',
        'affect_on_price',
        'end_at'
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class)->withoutGlobalScope(SoftDeletingScope::class);
    }

    public function field_charts(): HasMany
    {
        return $this->hasMany(FieldChart::class);
    }


    
    public static function boot()
	{
		parent::boot();


		static::addGlobalScope('ordered', function (Builder $query) {
			$query->orderBy('sort');
		});

	}

}
