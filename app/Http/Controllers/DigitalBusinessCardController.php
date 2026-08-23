<?php

namespace App\Http\Controllers;

use App\Models\DigitalBusinessCard;

class DigitalBusinessCardController extends Controller
{
    public function digital_business_card_detail($slug)
    {
        $digital_business_card = $this->loadCard((string) $slug);

        return view('main.business-card', compact('digital_business_card'));
    }

    public function show($slug)
    {
        return view('main.business-card', [
            'digital_business_card' => $this->loadCard((string) $slug),
        ]);
    }

    private function loadCard(String $slug): DigitalBusinessCard
    {
        return DigitalBusinessCard::with([
            'digital_business_card_blocks' => function ($query) {
                $query->orderBy('sort')->with([
                    'text_blocks' => fn ($q) => $q->orderBy('sort'),
                    'link_blocks' => fn ($q) => $q->orderBy('sort'),
                    'social_blocks' => fn ($q) => $q->orderBy('sort'),
                    'map_blocks' => fn ($q) => $q->orderBy('sort'),
                    'faq_blocks' => fn ($q) => $q->orderBy('sort')->with(['items' => fn ($itemsQuery) => $itemsQuery->orderBy('sort')]),
                    'gallery_blocks' => fn ($q) => $q->orderBy('sort')->with(['items' => fn ($itemsQuery) => $itemsQuery->orderBy('sort')]),
                ]);
            },
        ])->where('slug',$slug)->first();
    }
}
