<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalBusinessCardEditorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'des' => $this->des,
            'logo' => $this->logo,
            'image_background' => $this->image_background,
            'page_background_image' => $this->page_background_image,
            'header_text_color' => $this->header_text_color,
            'title_font_family' => $this->title_font_family,
            'description_font_family' => $this->description_font_family,
            'blocks' => DigitalBusinessCardEditorBlockResource::collection($this->whenLoaded('blocks')),
        ];
    }
}