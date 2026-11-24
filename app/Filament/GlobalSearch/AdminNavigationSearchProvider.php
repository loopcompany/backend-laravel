<?php

namespace App\Filament\GlobalSearch;

use Filament\Facades\Filament;
use Filament\GlobalSearch\Contracts\GlobalSearchProvider;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\Navigation\NavigationItem;

/** Search only the current administrator's visible navigation, never hidden resources. */
class AdminNavigationSearchProvider implements GlobalSearchProvider
{
    public function getResults(string $query): ?GlobalSearchResults
    {
        $results = GlobalSearchResults::make();
        $admin = Filament::auth()->user();
        if (! $admin || ! $admin->is_active || trim($query) === '') {
            return $results;
        }

        $matches = [];
        foreach (Filament::getNavigation() as $group) {
            $matches = [...$matches, ...$this->searchItems($group->getItems(), $query, (string) $group->getLabel())];
        }

        return $results->category('بخش‌های پنل', array_slice($matches, 0, 30));
    }

    /** @param iterable<NavigationItem> $items @return array<GlobalSearchResult> */
    public function searchItems(iterable $items, string $query, string $group = ''): array
    {
        $words = preg_split('/\s+/u', $this->normalize($query), -1, PREG_SPLIT_NO_EMPTY);
        if (! $words) {
            return [];
        }
        $results = [];
        foreach ($items as $item) {
            if (! $item->isVisible()) {
                continue;
            }
            $label = $item->getLabel();
            $text = $this->normalize($group.' '.$label);
            $url = $item->getUrl();
            if ($url && collect($words)->every(fn ($word) => str_contains($text, $word))) {
                $results[] = new GlobalSearchResult($label, $url, $group ? ['گروه' => $group] : []);
            }
            $results = [...$results, ...$this->searchItems($item->getChildItems(), $query, $group)];
        }
        return $results;
    }

    private function normalize(string $text): string
    {
        return mb_strtolower(trim(preg_replace('/[\s\x{200C}\x{200E}\x{200F}]+/u', ' ', strtr($text, ['ي' => 'ی', 'ك' => 'ک']))));
    }
}
