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

    public function test_download_cards_have_six_ordered_channels_and_three_live_calls_to_action(): void
    {
        $xpath=$this->xpath($this->get('/')->assertOk()->getContent());
        $cards=$xpath->query('//div[@class="app-card"]');
        $this->assertCount(3,$cards);
        foreach ($cards as $index=>$card) {
            $links=$xpath->query('.//div[@class="store-col"]/a',$card);
            $this->assertCount(6,$links);
            $this->assertSame('/'.($index===1?'LoopTech.apk':'LoopUser.apk'),parse_url($links[0]->getAttribute('href'),PHP_URL_PATH));
            foreach (['android','bazaar','myket','ios','ios-web','web'] as $position=>$channel) {
                $this->assertStringContainsString('store-btn--'.$channel,$links[$position]->getAttribute('class'));
            }
            $this->assertSame('#',$links[1]->getAttribute('href'));
            $this->assertNotSame('#',$links[5]->getAttribute('href'));
        }
        $this->assertCount(0,$xpath->query('//a[contains(@class,"get-btn")]'));
        $cta=$xpath->query('//div[@class="eco-cta reveal"]/a');
        $this->assertCount(3,$cta);
        $this->assertSame('#windows-app',$cta[1]->getAttribute('href'));
    }
}
