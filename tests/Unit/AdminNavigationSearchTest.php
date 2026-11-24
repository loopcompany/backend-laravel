<?php

namespace Tests\Unit;

use App\Filament\GlobalSearch\AdminNavigationSearchProvider;
use Filament\Navigation\NavigationItem;
use Tests\TestCase;

class AdminNavigationSearchTest extends TestCase
{
    public function test_matches_partial_titles_and_groups_but_excludes_hidden_navigation(): void
    {
        $provider = new AdminNavigationSearchProvider;
        $items = [
            NavigationItem::make('کاربران سازمانی')->url('/admin/organizations'),
            NavigationItem::make('کاربران عادی')->url('/admin/users'),
            NavigationItem::make('حساب‌های محرمانه')->url('/admin/hidden')->hidden(),
        ];
        $this->assertCount(2, $provider->searchItems($items, 'كاربران', 'مدیریت کاربران'));
        $matches = $provider->searchItems($items, 'سازمانی', 'مدیریت کاربران');
        $this->assertCount(1, $matches);
        $this->assertSame('/admin/organizations', $matches[0]->url);
        $this->assertCount(0, $provider->searchItems($items, 'محرمانه'));
        $this->assertCount(0, $provider->searchItems($items, '   '));
    }

    public function test_normalizes_persian_and_arabic_letters_and_searches_children(): void
    {
        $provider = new AdminNavigationSearchProvider;
        $child = NavigationItem::make('کدهای پرسنلی')->url('/admin/personnel');
        $parent = NavigationItem::make('مدیریت')->childItems([$child]);
        $matches = $provider->searchItems([$parent], 'كدهاي پرسنلی');
        $this->assertCount(1, $matches);
        $this->assertSame('/admin/personnel', $matches[0]->url);
    }
}
