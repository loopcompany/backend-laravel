<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\EloquentSortable\SortableTrait;

class LinkBlock extends Model
{
    use SortableTrait;
    public $sortable = [
        'order_column_name' => 'sort',
        'sort_when_creating' => true,
    ];
    protected $fillable = ['digital_business_card_id', 'digital_business_card_block_id', 'title', 'link', 'image', 'animation_type', 'sort', 'color'];

    protected $casts = [
        'digital_business_card_id' => 'integer',
        'digital_business_card_block_id' => 'integer',
        'sort' => 'integer',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(DigitalBusinessCardBlock::class, 'digital_business_card_block_id');
    }
}
