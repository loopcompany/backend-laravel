<?php

namespace App\Services;

use App\Models\DigitalBusinessCard;
use App\Models\DigitalBusinessCardBlock;
use App\Models\FaqBlock;
use App\Models\FaqItem;
use App\Models\GalleryBlock;
use App\Models\GalleryItem;
use App\Models\LinkBlock;
use App\Models\MapBlock;
use App\Models\SocialBlock;
use App\Models\TextBlock;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DigitalBusinessCardEditorService
{
    public function save(DigitalBusinessCard $card, array $payload): DigitalBusinessCard
    {
        return DB::transaction(function () use ($card, $payload) {
            $card->fill(Arr::only($payload, ['title', 'slug', 'des', 'logo', 'image_background', 'page_background_image', 'header_text_color', 'title_font_family', 'description_font_family']));
            $card->save();

            $blocks = collect($payload['blocks'] ?? []);
            $existingBlocks = $card->blocks()->get()->keyBy('id');
            $keptBlockIds = [];

            foreach ($blocks as $index => $blockData) {
                $block = $this->upsertBlock($card, $existingBlocks, $blockData, $index);
                $keptBlockIds[] = $block->id;
                $this->syncBlockDetail($block, (array) data_get($blockData, 'data', []));
            }

            $card->blocks()
                ->when(! empty($keptBlockIds), fn ($query) => $query->whereNotIn('id', $keptBlockIds))
                ->when(empty($keptBlockIds), fn ($query) => $query)
                ->get()
                ->each
                ->delete();

            return $card;
        });
    }

    private function upsertBlock(DigitalBusinessCard $card, $existingBlocks, array $blockData, int $index): DigitalBusinessCardBlock
    {
        $blockId = data_get($blockData, 'id');

        $block = is_numeric($blockId) && $existingBlocks->has((int) $blockId)
            ? $existingBlocks->get((int) $blockId)
            : new DigitalBusinessCardBlock();

        $block->digital_business_card_id = $card->id;
        $block->type = data_get($blockData, 'type');
        $block->title = data_get($blockData, 'title', '');
        $block->color = data_get($blockData, 'color', '#000');
        $block->background_color = data_get($blockData, 'background_color', '#ffffff');
        $block->sort = (int) data_get($blockData, 'sort', $index + 1);
        $block->save();

        return $block;
    }

    private function syncBlockDetail(DigitalBusinessCardBlock $block, array $data): void
    {
        $this->deleteOtherDetails($block);

        match ($block->type) {
            DigitalBusinessCardBlock::TYPE_TEXT => $this->syncTextBlock($block, $data),
            DigitalBusinessCardBlock::TYPE_LINK => $this->syncLinkBlock($block, $data),
            DigitalBusinessCardBlock::TYPE_SOCIAL => $this->syncSocialBlock($block, $data),
            DigitalBusinessCardBlock::TYPE_MAP => $this->syncMapBlock($block, $data),
            DigitalBusinessCardBlock::TYPE_FAQ => $this->syncFaqBlock($block, $data),
            DigitalBusinessCardBlock::TYPE_GALLERY => $this->syncGalleryBlock($block, $data),
            default => null,
        };
    }

    private function deleteOtherDetails(DigitalBusinessCardBlock $block): void
    {
        TextBlock::where('digital_business_card_block_id', $block->id)->delete();
        LinkBlock::where('digital_business_card_block_id', $block->id)->delete();
        SocialBlock::where('digital_business_card_block_id', $block->id)->delete();
        MapBlock::where('digital_business_card_block_id', $block->id)->delete();
        FaqBlock::where('digital_business_card_block_id', $block->id)->delete();
        GalleryBlock::where('digital_business_card_block_id', $block->id)->delete();
    }

    private function syncTextBlock(DigitalBusinessCardBlock $block, array $data): void
    {
        TextBlock::updateOrCreate(
            ['digital_business_card_block_id' => $block->id],
            [
                'digital_business_card_id' => $block->digital_business_card_id,
                'descriptions' => (string) data_get($data, 'descriptions', ''),
                'sort' => $block->sort,
                'color' => $block->color,
            ]
        );
    }

    private function syncLinkBlock(DigitalBusinessCardBlock $block, array $data): void
    {
        LinkBlock::updateOrCreate(
            ['digital_business_card_block_id' => $block->id],
            [
                'digital_business_card_id' => $block->digital_business_card_id,
                'title' => $block->title,
                'link' => (string) data_get($data, 'link', ''),
                'image' => data_get($data, 'image'),
                'animation_type' => data_get($data, 'animation_type'),
                'sort' => $block->sort,
                'color' => $block->color,
            ]
        );
    }

    private function syncSocialBlock(DigitalBusinessCardBlock $block, array $data): void
    {
        SocialBlock::updateOrCreate(
            ['digital_business_card_block_id' => $block->id],
            [
                'digital_business_card_id' => $block->digital_business_card_id,
                'title' => $block->title,
                'link' => (string) data_get($data, 'link', ''),
                'animation_type' => data_get($data, 'animation_type'),
                'icon' => data_get($data, 'icon'),
                'sort' => $block->sort,
                'color' => $block->color,
            ]
        );
    }

    private function syncMapBlock(DigitalBusinessCardBlock $block, array $data): void
    {
        MapBlock::updateOrCreate(
            ['digital_business_card_block_id' => $block->id],
            [
                'digital_business_card_id' => $block->digital_business_card_id,
                'title' => $block->title,
                'address' => (string) data_get($data, 'address', ''),
                'link' => (string) data_get($data, 'link', ''),
                'latitude' => data_get($data, 'latitude'),
                'longitude' => data_get($data, 'longitude'),
                'sort' => $block->sort,
                'color' => $block->color,
            ]
        );
    }

    private function syncFaqBlock(DigitalBusinessCardBlock $block, array $data): void
    {
        $faqBlock = FaqBlock::updateOrCreate(
            ['digital_business_card_block_id' => $block->id],
            [
                'digital_business_card_id' => $block->digital_business_card_id,
                'title' => $block->title,
                'descriptions' => '',
                'sort' => $block->sort,
                'title_color' => $block->color,
                'descriptions_color' => $block->color,
            ]
        );

        $this->syncFaqItems($faqBlock, (array) data_get($data, 'items', []));
    }

    private function syncGalleryBlock(DigitalBusinessCardBlock $block, array $data): void
    {
        $items = (array) data_get($data, 'items', []);
        $firstImage = data_get($items, '0.image');

        $galleryBlock = GalleryBlock::updateOrCreate(
            ['digital_business_card_block_id' => $block->id],
            [
                'digital_business_card_id' => $block->digital_business_card_id,
                'title' => $block->title,
                'descriptions' => '',
                'file_path' => (string) ($firstImage ?? ''),
                'sort' => $block->sort,
            ]
        );

        $this->syncGalleryItems($galleryBlock, $items);
    }

    private function syncFaqItems(FaqBlock $faqBlock, array $items): void
    {
        $existingIds = $faqBlock->items()->pluck('id')->all();
        $keptIds = [];

        foreach ($items as $index => $itemData) {
            $itemId = data_get($itemData, 'id');

            $faqItem = is_numeric($itemId) && in_array((int) $itemId, $existingIds, true)
                ? FaqItem::query()->where('faq_block_id', $faqBlock->id)->whereKey((int) $itemId)->firstOrFail()
                : new FaqItem();

            $faqItem->faq_block_id = $faqBlock->id;
            $faqItem->question = (string) data_get($itemData, 'question', '');
            $faqItem->answer = (string) data_get($itemData, 'answer', '');
            $faqItem->sort = (int) data_get($itemData, 'sort', $index + 1);
            $faqItem->save();

            $keptIds[] = $faqItem->id;
        }

        if (! empty($existingIds)) {
            $faqBlock->items()->whereNotIn('id', $keptIds)->delete();
        }
    }

    private function syncGalleryItems(GalleryBlock $galleryBlock, array $items): void
    {
        $existingIds = $galleryBlock->items()->pluck('id')->all();
        $keptIds = [];

        foreach ($items as $index => $itemData) {
            $itemId = data_get($itemData, 'id');

            $galleryItem = is_numeric($itemId) && in_array((int) $itemId, $existingIds, true)
                ? GalleryItem::query()->where('gallery_block_id', $galleryBlock->id)->whereKey((int) $itemId)->firstOrFail()
                : new GalleryItem();

            $galleryItem->gallery_block_id = $galleryBlock->id;
            $galleryItem->image = (string) data_get($itemData, 'image', '');
            $galleryItem->caption = data_get($itemData, 'caption');
            $galleryItem->sort = (int) data_get($itemData, 'sort', $index + 1);
            $galleryItem->save();

            $keptIds[] = $galleryItem->id;
        }

        if (! empty($existingIds)) {
            $galleryBlock->items()->whereNotIn('id', $keptIds)->delete();
        }
    }
}