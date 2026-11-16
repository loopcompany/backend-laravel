<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

class EmployerTestResetEditsTest extends TestCase
{
    use RefreshDatabase,MakesPeople;
    private function payload(): array
    {
        $user=$this->makeUser();$template=$this->makeOrder($user);Sanctum::actingAs($user);
        return ['address_id'=>$template->user_address_id,'category_id'=>$template->category_id,'total_price'=>700000,'date'=>now()->addDay()->format('Y-m-d'),'time'=>'10 - 12','platform'=>'android'];
    }
    public function test_female_technician_selection_is_rejected_for_old_and_new_clients(): void
    {
        $data=$this->payload();
        foreach ([['technician_gender'=>'female'],['female_count'=>1]] as $choice) {
            $this->postJson('/api/orders/submit',$data+$choice)->assertUnprocessable()->assertJsonValidationErrors(array_keys($choice));
        }
        $this->assertSame(1,\App\Models\Order::count());
    }
    public function test_male_technician_selection_remains_available(): void
    {
        $this->postJson('/api/orders/submit',$this->payload()+['technician_gender'=>'male','female_count'=>0])->assertCreated();
    }
    public function test_union_verification_link_uses_same_tab_without_popup_handler(): void
    {
        $html=$this->get('/')->assertOk()->getContent();$dom=new \DOMDocument();@$dom->loadHTML('<?xml encoding="UTF-8">'.$html);$xpath=new \DOMXPath($dom);
        $link=$xpath->query('//a[contains(@href,"ecunion.ir/verify/")]')->item(0);
        $this->assertNotNull($link);$this->assertFalse($link->hasAttribute('target'));$this->assertFalse($link->hasAttribute('onclick'));
    }
}
