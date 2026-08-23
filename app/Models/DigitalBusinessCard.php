<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitalBusinessCard extends Model
{
    protected $fillable = ['title', 'image_background', 'page_background_image', 'header_text_color', 'logo', 'des', 'title_font_family', 'description_font_family', 'slug'];

    protected $casts = [
        'id' => 'integer',
    ];

    public function blocks()
    {
        return $this->hasMany(DigitalBusinessCardBlock::class, 'digital_business_card_id')->orderBy('sort');
    }

    public function digital_business_card_blocks()
    {
        return $this->blocks();
    }

    public function text_blocks()
    {
        return $this->hasMany(TextBlock::class, 'digital_business_card_id');
    }

    public function link_blocks()
    {
        return $this->hasMany(LinkBlock::class, 'digital_business_card_id');
    }

    public function social_blocks()
    {
        return $this->hasMany(SocialBlock::class, 'digital_business_card_id');
    }

    public function map_blocks()
    {
        return $this->hasMany(MapBlock::class, 'digital_business_card_id');
    }

    public function faq_blocks()
    {
        return $this->hasMany(FaqBlock::class, 'digital_business_card_id');
    }

    public function gallery_blocks()
    {
        return $this->hasMany(GalleryBlock::class, 'digital_business_card_id');
    }
}
