<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EloquentSortable\SortableTrait;

class FaqItem extends Model
{
    use SortableTrait;

    public $sortable = [
        'order_column_name' => 'sort',
        'sort_when_creating' => true,
    ];

    protected $fillable = [
        'faq_block_id',
        'question',
        'answer',
        'sort',
    ];

    protected $casts = [
        'faq_block_id' => 'integer',
        'sort' => 'integer',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(FaqBlock::class, 'faq_block_id');
    }
}