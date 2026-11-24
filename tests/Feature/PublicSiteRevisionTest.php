<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteRevisionTest extends TestCase
{
    use RefreshDatabase;

    private function xpath(string $html): \DOMXPath
    {
        $document=new \DOMDocument();
        @$document->loadHTML($html);
        return new \DOMXPath($document);
    }

    public function test_public_pages_share_desktop_and_mobile_navigation_with_real_destinations(): void
    {
        if (getenv('LOOP_SITE_PREVIEW')) \Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8765');
        $reference=null;
        foreach (['/','/about','/faqs','/contact','/blogs','/careers'] as $path) {
            $response=$this->get($path)->assertOk();
            $xpath=$this->xpath($response->getContent());
            $primary=$xpath->query('//nav[@class="loop-primary-links"]/a');
            $this->assertCount(5,$primary);
            $mobile=$xpath->query('//div[@class="loop-menu-panel"]/nav/a');
            $this->assertCount(10,$mobile);
            $links=[];
            foreach ($mobile as $link) {
                $url=$link->getAttribute('href');
                $this->assertNotSame('#',$url);
                $links[]=$url;
            }
            foreach ($primary as $link) $this->assertContains($link->getAttribute('href'),$links);
            if ($reference!==null) $this->assertSame($reference,$links);
            $reference=$links;
            if ($directory=getenv('LOOP_SITE_PREVIEW')) {
                file_put_contents($directory.'/'.($path==='/'?'index':trim($path,'/')).'.html',$this->get('http://127.0.0.1:8765'.$path)->getContent());
            }
        }
    }

    // Employer request of 2026-11-23 (item 5): iOS is shown disabled with «(به زودی)» and the
    // plain «وب‌اپلیکیشن لوپ» entry is removed; the iOS web-app entry stays. The old test
    // demanded six live-ordered channels including that web entry.
    public function test_download_cards_have_five_ordered_channels_with_ios_coming_soon_and_no_web_app_entry(): void
    {
        $xpath=$this->xpath($this->get('/')->assertOk()->getContent());
        $cards=$xpath->query('//div[@class="app-card"]');
        $this->assertCount(3,$cards);
        foreach ($cards as $index=>$card) {
            $buttons=$xpath->query('.//div[@class="store-col"]/*[contains(@class,"store-btn")]',$card);
            $this->assertCount(5,$buttons);
            foreach (['android','bazaar','myket','ios','ios-web'] as $position=>$channel) {
                $this->assertStringContainsString('store-btn--'.$channel,$buttons[$position]->getAttribute('class'));
            }
            $links=$xpath->query('.//div[@class="store-col"]/a',$card);
            $this->assertCount(4,$links);
            $this->assertSame('/downloads/android/'.($index===1?'LoopTech-1.0.2.apk':'LoopUser-1.0.3.apk'),parse_url($links[0]->getAttribute('href'),PHP_URL_PATH));
            $this->assertSame('#',$links[1]->getAttribute('href'));
            $ios=$buttons[3];
            $this->assertSame('span',$ios->tagName,'iOS must not be a link');
            $this->assertStringContainsString('is-soon',$ios->getAttribute('class'));
            $this->assertSame('true',$ios->getAttribute('aria-disabled'));
            $this->assertStringContainsString('به زودی',trim(preg_replace('/\s+/u',' ',$ios->textContent)));
            $this->assertNotSame('#',$links[3]->getAttribute('href'));
            $this->assertCount(0,$xpath->query('.//*[contains(@class,"store-btn--web")]',$card),'the plain web-app entry is removed');
        }
        $this->assertCount(0,$xpath->query('//a[contains(@class,"get-btn")]'));
        $cta=$xpath->query('//div[@class="eco-cta reveal"]/a');
        $this->assertCount(3,$cta);
        $this->assertSame('#windows-app',$cta[1]->getAttribute('href'));
    }
}
