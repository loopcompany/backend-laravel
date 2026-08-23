<?php

namespace Tests\Feature;

use App\Models\DigitalBusinessCard;
use App\Models\DigitalBusinessCardBlock;
use App\Models\TextBlock;
use App\Support\DigitalBusinessCardTypography;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDigitalBusinessCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_card_page_is_accessible_to_guests_and_renders_content(): void
    {
        $card = DigitalBusinessCard::create([
            'title' => 'کارت عمومی',
            'des' => 'توضیح عمومی',
            'header_text_color' => '#ffffff',
        ]);

        $block = DigitalBusinessCardBlock::create([
            'digital_business_card_id' => $card->id,
            'type' => DigitalBusinessCardBlock::TYPE_TEXT,
            'title' => 'بخش معرفی',
            'sort' => 1,
            'color' => '#0f172a',
            'background_color' => '#ffffff',
        ]);

        TextBlock::create([
            'digital_business_card_id' => $card->id,
            'digital_business_card_block_id' => $block->id,
            'descriptions' => DigitalBusinessCardTypography::embedTypographyInText('متن عمومی', [
                'description_font' => 'iransans',
            ]),
            'sort' => 1,
            'color' => '#0f172a',
        ]);

        $response = $this->get(route('public.cards.show', $card));

        $response->assertOk();
        $response->assertSee('کارت عمومی', false);
        $response->assertSee('متن عمومی', false);
        $response->assertSee("font-family: 'IRANSansFont', sans-serif;", false);
    }
}