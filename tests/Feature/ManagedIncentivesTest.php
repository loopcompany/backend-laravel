<?php
namespace Tests\Feature;

use App\Jobs\SendManagedCode;
use App\Models\{CodeDelivery, IncentivePlan, Organization, ReferralCode, ReferralCommission, TechnicianTransaction};
use App\Services\{CodeDeliveryService, ReferralAgentService, ReferralCommissionTable, SmsService, TechnicianIncentiveService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Http, Queue};
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

class ManagedIncentivesTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    private function managed(array $data = []): array
    {
        $user=$this->makeUser(['account_type'=>'company','is_special'=>true,'is_referral_agent'=>true,'registration_method'=>3]);
        Organization::create(['user_id'=>$user->id,'organization_name'=>'شرکت آزمون','organization_code'=>'ORG'.$user->id,'organization_phone'=>'02100000000','organization_address'=>'آدرس','manager_full_name'=>'مدیر','manager_national_code'=>str_pad((string)$user->id,10,'0',STR_PAD_LEFT),'profile_status'=>'approved']);
        $code=ReferralCode::create(array_merge(['user_id'=>$user->id,'policy_version'=>ReferralCode::CURRENT_POLICY,'status'=>'active','commission_mode'=>'table'], $data));
        return [$user,$code];
    }

    private function commissionReviewer(): \App\Models\Admin
    {
        $admin = \App\Models\Admin::create(['name' => 'حسابدار', 'email' => 'reviewer@test.local', 'password' => 'secret', 'is_active' => true]);
        $admin->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'edit-referral-withdrawals', 'guard_name' => 'admin']));

        return $admin;
    }

    public function test_workbook_pairs_and_unknown_amounts_are_not_interpolated(): void
    {
        $calculator=app(ReferralCommissionTable::class);
        foreach (config('referral_commissions.services') as $amount=>$reward) $this->assertSame($reward,$calculator->calculate($amount,0));
        foreach (config('referral_commissions.goods') as $amount=>$reward) $this->assertSame($reward,$calculator->calculate(0,$amount));
        $this->assertSame(200000,$calculator->calculate(700000,3000000));
        foreach ([1,699999,700001,50000000001] as $amount) $this->assertNull($calculator->calculate($amount,0));
    }

    public function test_referral_endpoint_no_longer_accepts_even_previously_issued_codes(): void
    {
        [$owner, $code] = $this->managed();
        Sanctum::actingAs($owner);
        $this->postJson('/api/referral-codes/check', ['code' => $code->code])->assertGone()->assertJsonPath('error_code', 'REFERRAL_CODES_RETIRED');
        $this->assertNull($code->fresh()->used_at);
    }

    public function test_invalid_recipient_cannot_receive_a_new_policy_code(): void
    {
        $user=$this->makeUser();
        $this->expectException(ValidationException::class);
        ReferralCode::create(['user_id'=>$user->id,'policy_version'=>ReferralCode::CURRENT_POLICY]);
    }

    public function test_commission_requires_settlement_and_is_not_duplicated(): void
    {
        [$owner,$code]=$this->managed();
        $order=$this->makeOrder($owner,['pakar_price'=>700000,'technician_price'=>700000]);
        $code->update(['used_order_id'=>$order->id,'status'=>'used']);
        $order->updateQuietly(['referral_code_id'=>$code->id,'payment_status'=>1]);
        $service=app(ReferralAgentService::class);
        $this->assertSame('pending',$service->syncForOrder($order)->status);
        $order->updateQuietly(['settled_at'=>now()]);
        $this->assertSame('pending',$service->syncForOrder($order)->status);
        $reviewer = $this->commissionReviewer();
        $service->approveForWithdrawal($service->syncForOrder($order), $reviewer);
        $service->approveForWithdrawal($service->syncForOrder($order), $reviewer);
        $this->assertSame('available',$service->syncForOrder($order)->status);
        $this->assertSame(100000,$service->balances($owner)['available']);
        $service->syncForOrder($order);
        $this->assertSame(1,ReferralCommission::count());
        $this->assertSame(100000,$service->balances($owner)['available']);

    }

    public function test_unknown_amount_waits_for_manual_settlement(): void
    {
        [$owner,$code]=$this->managed();
        $order=$this->makeOrder($owner,['technician_price'=>777777]);
        $code->update(['used_order_id'=>$order->id,'status'=>'used']);
        $order->updateQuietly(['referral_code_id'=>$code->id,'payment_status'=>1,'settled_at'=>now()]);
        $service=app(ReferralAgentService::class);
        $commission=$service->syncForOrder($order);
        $this->assertSame('pending',$commission->status);
        $this->assertSame(0,$service->balances($owner)['available']);
        $service->settleManual($commission,123456);
        $this->assertSame(0,$service->balances($owner)['available']);
        $service->approveForWithdrawal($commission, $this->commissionReviewer());
        $this->assertSame(123456,$service->balances($owner)['available']);
        $this->assertSame('available',$commission->fresh()->status);
    }

    public function test_manual_amount_entered_before_closure_remains_authoritative(): void
    {
        [$owner,$code]=$this->managed();
        $order=$this->makeOrder($owner,['technician_price'=>777777]);
        $code->update(['used_order_id'=>$order->id,'status'=>'used']);
        $order->updateQuietly(['referral_code_id'=>$code->id]);
        $service=app(ReferralAgentService::class);
        $commission=$service->syncForOrder($order);
        $service->settleManual($commission,34567);
        $this->assertSame('pending',$commission->fresh()->status);
        $service->syncForOrder($order);
        $order->updateQuietly(['payment_status'=>1,'settled_at'=>now()]);
        $this->assertSame(34567,$service->syncForOrder($order)->commission_amount);
        $service->approveForWithdrawal($commission, $this->commissionReviewer());
        $this->assertSame(34567,$service->balances($owner)['available']);
    }

    public function test_dispatch_audits_recipient_channel_and_provider_ack_and_does_not_resend(): void
    {
        Queue::fake();
        config(['smsir.enabled'=>true,'smsir.api-key'=>'test','smsir.line-number'=>'30000000']);
        Http::preventStrayRequests();
        Http::fake(['api.sms.ir/*'=>Http::response(['status'=>1],200)]);
        [$owner,$code]=$this->managed();
        $service=app(CodeDeliveryService::class);
        $delivery=$service->issue($code,$owner,null);
        Queue::assertPushed(SendManagedCode::class);
        $service->send($delivery->id);
        $service->send($delivery->id);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request)=>$request['mobiles']===[$owner->phone] && str_contains($request['messageText'],$code->code));
        $this->assertSame('sent',$delivery->fresh()->status);
        $this->assertNotNull($delivery->fresh()->sent_at);
        $this->assertSame('sent',$code->fresh()->status);
        $this->assertSame('recorded',$service->issue($code->fresh(),$owner,null,'phone')->status);
    }

    public function test_disabled_or_failed_sms_is_never_marked_sent(): void
    {
        Queue::fake();
        [$owner,$code]=$this->managed();
        $service=app(CodeDeliveryService::class);
        config(['smsir.enabled'=>false]);
        $delivery=$service->issue($code,$owner,null);
        $service->send($delivery->id);
        $this->assertSame('disabled',$delivery->fresh()->status);
        $this->assertNull($delivery->fresh()->sent_at);
        config(['smsir.enabled'=>true,'smsir.api-key'=>'test','smsir.line-number'=>'30000000']);
        Http::fake(['api.sms.ir/*'=>Http::response(['status'=>0],400)]);
        $delivery=$service->issue($code,$owner,null);
        $service->send($delivery->id);
        $this->assertSame('failed',$delivery->fresh()->status);
    }

    public function test_cash_incentive_is_credited_only_once_and_is_private(): void
    {
        $tech=$this->makeTechnician(['wallet'=>0]);
        $plan=IncentivePlan::create(['technician_id'=>$tech->id,'description'=>'پاداش آزمون','benefit_type'=>'reward','amount'=>50000,'status'=>0]);
        Sanctum::actingAs($this->makeTechnician());
        $this->postJson('/api/technician/incentive-plans/'.$plan->id.'/redeem')->assertNotFound();
        Sanctum::actingAs($tech);
        $this->postJson('/api/technician/incentive-plans/'.$plan->id.'/redeem')->assertOk()->assertJsonPath('success',true);
        $this->postJson('/api/technician/incentive-plans/'.$plan->id.'/redeem')->assertOk();
        $this->assertSame(50000,(int)$tech->fresh()->wallet);
        $this->assertSame(1,TechnicianTransaction::where('referenceId','INCENTIVE_'.$plan->id)->count());
    }

    public function test_expired_incentive_cannot_credit_wallet(): void
    {
        $tech=$this->makeTechnician(['wallet'=>0]);
        $plan=IncentivePlan::create(['technician_id'=>$tech->id,'description'=>'منقضی','benefit_type'=>'wallet','amount'=>50000,'status'=>0,'end_at'=>now()->subSecond()]);
        Sanctum::actingAs($tech);
        $this->postJson('/api/technician/incentive-plans/'.$plan->id.'/redeem')->assertUnprocessable();
        $this->assertSame(0,(int)$tech->fresh()->wallet);
    }

    public function test_non_cash_request_needs_admin_fulfillment(): void
    {
        $tech=$this->makeTechnician(['wallet'=>0]);
        $plan=IncentivePlan::create(['technician_id'=>$tech->id,'description'=>'اقلام','benefit_type'=>'company_item','benefit_location'=>'دفتر لوپ','benefit_code'=>'ITEM-1','status'=>0]);
        $service=app(TechnicianIncentiveService::class);
        $requested=$service->request($tech,$plan->id);
        $this->assertNotNull($requested->requested_at);
        $this->assertNull($requested->redeemed_at);
        $fulfilled=$service->fulfill($plan->id,1);
        $this->assertNotNull($fulfilled->redeemed_at);
        $this->assertSame(0,(int)$tech->fresh()->wallet);
        $this->assertSame(0,TechnicianTransaction::count());
    }
    public function test_view_only_incentive_admin_cannot_mark_entitlements_used(): void
    {
        $technician=$this->makeTechnician();
        $plan=IncentivePlan::create(['technician_id'=>$technician->id,'benefit_type'=>'legacy','description'=>'Legacy benefit','status'=>0]);
        $admin=\App\Models\Admin::create(['name'=>'Viewer','email'=>'incentive-viewer@test.local','password'=>'password','is_active'=>true,'staff_type'=>\App\Models\Admin::STAFF_OFFICE]);
        $admin->givePermissionTo(['access-admin-panel','view-incentive-plans']);
        $this->actingAs($admin,'admin');
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        \Livewire\Livewire::test(\App\Filament\Resources\IncentivePlanResource\Pages\ListIncentivePlans::class)
            ->assertTableActionHidden('mark_used',$plan)
            ->call('mountTableAction','mark_used',(string)$plan->id)
            ->call('callMountedTableAction');
        $this->assertSame(0,(int)$plan->fresh()->status);
    }

    public function test_consumed_incentive_audit_cannot_be_deleted(): void
    {
        $technician=$this->makeTechnician();
        $plan=IncentivePlan::create(['technician_id'=>$technician->id,'benefit_type'=>'wallet','amount'=>150000,'description'=>'Reward','status'=>0]);
        app(TechnicianIncentiveService::class)->request($technician,$plan->id);
        $this->assertFalse(\App\Filament\Resources\IncentivePlanResource::canDelete($plan->fresh()));
        $this->assertFalse(\App\Filament\Resources\IncentivePlanResource::canDeleteAny());
        try { $plan->fresh()->delete();$this->fail('Consumed incentive was deleted'); }
        catch (ValidationException $exception) { $this->assertArrayHasKey('incentive',$exception->errors()); }
        $this->assertDatabaseHas('incentive_plans',['id'=>$plan->id,'status'=>1]);
        $this->assertSame(1,TechnicianTransaction::where('referenceId','INCENTIVE_'.$plan->id)->count());
    }
}
