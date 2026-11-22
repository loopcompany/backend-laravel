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
        $user=$this->makeUser(['has_access'=>1,'name'=>'علی','last_name'=>'رضایی','email'=>'profile@example.test','melicode'=>'1234567891','birth_date'=>'1370/01/01','city'=>'تهران','region'=>'08','postal_code'=>'1234567890','home_address'=>'تهران خیابان نمونه کوچه آزمایش پلاک ده واحد دو','home_number'=>'10','home_unit'=>'2','home_floor'=>'0']);$user->forceFill(['profile_completed_at'=>now()])->save();$template=$this->makeOrder($user);Sanctum::actingAs($user);
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
        // The union verifier requires the registered site's Referer; suppressing
        // it renders "domain not found" even with the correct domain and token.
        $this->assertSame('origin', $link->getAttribute('referrerpolicy'));
        $this->assertNotContains('noreferrer', preg_split('/\s+/', $link->getAttribute('rel')));
    }
}
