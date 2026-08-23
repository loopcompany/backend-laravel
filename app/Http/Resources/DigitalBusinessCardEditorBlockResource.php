<?php

namespace App\Http\Resources;

use App\Models\DigitalBusinessCardBlock;
use App\Support\DigitalBusinessCardTypography;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalBusinessCardEditorBlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var DigitalBusinessCardBlock $block */
        $block = $this->resource;

        return [
            'id' => $block->id,
            'type' => $block->type,
            'title' => $block->title,
            'sort' => $block->sort,
            'color' => $block->color,
            'background_color' => $block->background_color,
            'data' => $this->dataPayload($block),
        ];
    }

    private function dataPayload(DigitalBusinessCardBlock $block): array
    {
        $textDescriptions = (string) ($block->textBlock?->descriptions ?? '');

        return match ($block->type) {
            DigitalBusinessCardBlock::TYPE_TEXT => [
                'descriptions' => DigitalBusinessCardTypography::stripTypographyMarker($textDescriptions),
                'typography' => DigitalBusinessCardTypography::decodeTypography($textDescriptions),
            ],
            DigitalBusinessCardBlock::TYPE_LINK => [
                'link' => $block->linkBlock?->link,
                'image' => $block->linkBlock?->image,
                'animation_type' => $block->linkBlock?->animation_type,
                'typography' => DigitalBusinessCardTypography::decodeTypography((string) ($block->linkBlock?->title ?? '')),
            ],
            DigitalBusinessCardBlock::TYPE_SOCIAL => [
                'link' => $block->socialBlock?->link,
                'icon' => $block->socialBlock?->icon,
                'animation_type' => $block->socialBlock?->animation_type,
                'typography' => DigitalBusinessCardTypography::decodeTypography((string) ($block->socialBlock?->title ?? '')),
            ],
            DigitalBusinessCardBlock::TYPE_MAP => [
                'address' => $block->mapBlock?->address,
                'link' => $block->mapBlock?->link,
                'latitude' => $block->mapBlock?->latitude,
                'longitude' => $block->mapBlock?->longitude,
                'typography' => DigitalBusinessCardTypography::decodeTypography((string) ($block->mapBlock?->title ?? '')),
            ],
            DigitalBusinessCardBlock::TYPE_FAQ => [
                'items' => FaqItemResource::collection($block->faqBlock?->items ?? collect()),
                'typography' => DigitalBusinessCardTypography::decodeTypography((string) ($block->faqBlock?->descriptions ?? '')),
            ],
            DigitalBusinessCardBlock::TYPE_GALLERY => [
                'items' => GalleryItemResource::collection($block->galleryBlock?->items ?? collect()),
                'typography' => DigitalBusinessCardTypography::decodeTypography((string) ($block->galleryBlock?->descriptions ?? '')),
            ],
            default => [],
        };
    }
}