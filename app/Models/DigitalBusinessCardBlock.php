<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\EloquentSortable\SortableTrait;

class DigitalBusinessCardBlock extends Model
{
    use SortableTrait;
    public $sortable = [
        'order_column_name' => 'sort',
        'sort_when_creating' => true,
    ];
    protected $fillable = ['digital_business_card_id', 'type', 'title', 'sort', 'color', 'background_color'];

    protected $casts = [
        'digital_business_card_id' => 'integer',
        'sort' => 'integer',
    ];

    public const TYPE_TEXT = 'text';
    public const TYPE_LINK = 'link';
    public const TYPE_SOCIAL = 'social';
    public const TYPE_MAP = 'map';
    public const TYPE_FAQ = 'faq';
    public const TYPE_GALLERY = 'gallery';

    public static function types(): array
    {
        return [
            self::TYPE_TEXT,
            self::TYPE_LINK,
            self::TYPE_SOCIAL,
            self::TYPE_MAP,
            self::TYPE_FAQ,
            self::TYPE_GALLERY,
        ];
    }

    public function digital_business_card(): BelongsTo
    {
        return $this->belongsTo(DigitalBusinessCard::class, 'digital_business_card_id');
    }

    public function textBlock(): HasOne
    {
        return $this->hasOne(TextBlock::class, 'digital_business_card_block_id');
    }

    public function text_blocks(): HasOne
    {
        return $this->textBlock();
    }

    public function linkBlock(): HasOne
    {
        return $this->hasOne(LinkBlock::class, 'digital_business_card_block_id');
    }

    public function link_blocks(): HasOne
    {
        return $this->linkBlock();
    }

    public function socialBlock(): HasOne
    {
        return $this->hasOne(SocialBlock::class, 'digital_business_card_block_id');
    }

    public function social_blocks(): HasOne
    {
        return $this->socialBlock();
    }

    public function mapBlock(): HasOne
    {
        return $this->hasOne(MapBlock::class, 'digital_business_card_block_id');
    }

    public function map_blocks(): HasOne
    {
        return $this->mapBlock();
    }

    public function faqBlock(): HasOne
    {
        return $this->hasOne(FaqBlock::class, 'digital_business_card_block_id');
    }

    public function faq_blocks(): HasOne
    {
        return $this->faqBlock();
    }

    public function galleryBlock(): HasOne
    {
        return $this->hasOne(GalleryBlock::class, 'digital_business_card_block_id');
    }

    public function gallery_blocks(): HasOne
    {
        return $this->galleryBlock();
    }
}
