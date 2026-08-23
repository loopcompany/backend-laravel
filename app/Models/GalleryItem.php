<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EloquentSortable\SortableTrait;

class GalleryItem extends Model
{
    use SortableTrait;

    public $sortable = [
        'order_column_name' => 'sort',
        'sort_when_creating' => true,
    ];

    protected $fillable = [
        'gallery_block_id',
        'image',
        'caption',
        'sort',
    ];

    protected $casts = [
        'gallery_block_id' => 'integer',
        'sort' => 'integer',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(GalleryBlock::class, 'gallery_block_id');
    }
}