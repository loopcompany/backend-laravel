<?php

namespace Tests\Feature;

use App\Models\{Admin, Order, OrderQuote, OrderWorkflow, Organization, UserTransaction};
use App\Services\{OrderAccountingService, OrderWorkflowService, OrderWorkflowException, OrganizationAgreementService, OrderService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

class EmployerClarificationsTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    private function ready(): Order
    {
        $order=$this->makeOrder($this->makeUser());
        $workflow=app(OrderWorkflowService::class);
        $workflow->classify($order,['service_type'=>'onsite_repair','customer_type'=>'individual']);
        $workflow->approveReview($order);
        $quote=$workflow->createQuote($order,OrderQuote::KIND_INITIAL,['service_fee'=>500000,'visit_date'=>now()->addDay()->toDateString(),'visit_time'=>'10 - 12']);
        $workflow->decideQuote($quote,true);
        return $order->fresh();
    }

    private function admin(string $permission): Admin
    {
        $admin=Admin::create(['name'=>'Test','email'=>uniqid().'@test.local','password'=>'password','staff_type'=>Admin::STAFF_MANAGER]);
        $admin->forceFill(['is_active'=>true])->save();
        $admin->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate($permission,'admin'));
        return $admin;
    }

    public function test_all_company_payment_methods_require_accounting_but_wallet_and_cash_do_not(): void
    {
        foreach ([[1,'in_app',true],[2,'in_app',true],[4,'card_to_card',true],[4,'sheba',true],[4,'bank_transfer',true],[3,'in_app',false],[4,'cash',false]] as [$type,$method,$required]) {
            $transaction=UserTransaction::create(['user_id'=>$this->makeUser()->id,'price'=>500000,'type'=>$type,'status'=>100,'payment_method'=>$method]);
            $this->assertSame($required,$transaction->requiresAccountingReview());
            $this->assertSame($required?'pending':null,$transaction->fresh()->accounting_status);
        }
    }

    public function test_paid_order_requires_accountant_then_technician_manager_and_audit_is_preserved(): void
    {
        $order=$this->ready(); $technician=$this->makeTechnician();
        $transaction=UserTransaction::create(['order_id'=>$order->id,'user_id'=>$order->user_id,'price'=>500000,'type'=>2,'status'=>100]);
        $workflow=app(OrderWorkflowService::class); $accounting=app(OrderAccountingService::class);
        $this->assertThrows(fn ()=>$workflow->assignTechnician($order,$technician->id),OrderWorkflowException::class);
        $accountant=$this->admin('review-order-payments');
        $accounting->review($transaction,true,'verified',$accountant);
        $this->actingAs($accountant,'admin');
        $this->assertThrows(fn ()=>$workflow->assignTechnician($order,$technician->id),OrderWorkflowException::class);
        $manager=$this->admin('assign-paid-orders'); $this->actingAs($manager,'admin');
        $workflow->assignTechnician($order,$technician->id);
        $this->assertSame('accepted',$order->fresh()->workflow->assignment_status);
        $this->assertSame($manager->id,$order->fresh()->workflow->manager_assigned_by);
        $this->assertDatabaseHas('order_stage_logs',['order_id'=>$order->id,'action'=>'payment_accounting_approved']);
        $this->assertSame(100,(int)$transaction->fresh()->status);
        $this->assertSame(500000,(int)$transaction->fresh()->price);
    }

    public function test_customer_must_confirm_visit_before_departure_and_changed_schedule_requires_new_confirmation(): void
    {
        $order=$this->ready(); $technician=$this->makeTechnician(); $workflow=app(OrderWorkflowService::class);
        $workflow->assignTechnician($order,$technician->id);
        Sanctum::actingAs($technician);
        $this->postJson("/api/technician/orders/{$order->id}/set-off")->assertStatus(400);
        $this->assertNull($order->fresh()->set_off_at);
        $this->postJson("/api/orders/{$order->id}/visit/decision",['decision'=>'accept'])->assertForbidden();
        Sanctum::actingAs($order->user);
        $this->postJson("/api/orders/{$order->id}/visit/decision",['decision'=>'accept'])->assertOk();
        $order->refresh()->update(['time'=>'14 - 16']);
        $this->assertSame('pending',$order->fresh()->workflow->customer_visit_status);
        $this->postJson("/api/orders/{$order->id}/visit/decision",['decision'=>'accept'])->assertOk();
        Sanctum::actingAs($technician);
        $this->postJson("/api/technician/orders/{$order->id}/set-off")->assertOk();
        $this->assertNotNull($order->fresh()->set_off_at);
    }

    public function test_customer_can_request_recoordination_or_cancel_but_another_customer_cannot(): void
    {
        foreach (['recoordinate','cancel'] as $decision) {
            $order=$this->ready(); $workflow=app(OrderWorkflowService::class);
            $workflow->assignTechnician($order,$this->makeTechnician()->id);
            Sanctum::actingAs($this->makeUser());
            $this->postJson("/api/orders/{$order->id}/visit/decision",['decision'=>$decision,'note'=>'test'])->assertNotFound();
            Sanctum::actingAs($order->user);
            $this->postJson("/api/orders/{$order->id}/visit/decision",['decision'=>$decision])->assertUnprocessable();
            $this->postJson("/api/orders/{$order->id}/visit/decision",['decision'=>$decision,'note'=>'schedule unavailable'])->assertOk();
            $this->assertSame($decision==='cancel'?OrderWorkflow::STAGE_CANCELLED:OrderWorkflow::STAGE_ASSIGNMENT,$order->fresh()->workflow_stage);
            $this->assertNull($order->fresh()->set_off_at);
        }
    }

    public function test_agreements_are_explicit_scoped_and_retry_does_not_copy_previous_information(): void
    {
        $user=$this->makeUser(['account_type'=>'company']); Organization::factory()->profileApproved()->create(['user_id'=>$user->id]);
        Sanctum::actingAs($user);
        $this->getJson('/api/organization/agreements')->assertOk()->assertJsonCount(0,'data.requests');
        $this->postJson('/api/organization/agreements',['delivery_modes'=>['once_short']])->assertUnprocessable();
        $response=$this->postJson('/api/organization/agreements',['delivery_modes'=>['monthly_short']])->assertCreated()->assertJsonPath('data.draft_url',null)->assertJsonPath('data.final_url',null);
        $id=$response->json('data.id');
        $this->postJson('/api/organization/agreements',['delivery_modes'=>['project']])->assertUnprocessable();
        $request=\App\Models\OrganizationContractRequest::findOrFail($id);
        $request->update(['status'=>'2','information'=>'old information']);
        $this->postJson('/api/organization/agreements',['delivery_modes'=>['monthly_short'],'previous_request_id'=>$id])->assertCreated()->assertJsonPath('data.information',null)->assertJsonPath('data.previous_request_id',$id);
        Sanctum::actingAs($this->makeTechnician());
        $this->getJson('/api/organization/agreements')->assertForbidden();
    }

    public function test_final_agreement_download_requires_admin_publication_and_once_order_needs_no_agreement(): void
    {
        $user=$this->makeUser(['account_type'=>'company']); $organization=Organization::factory()->profileApproved()->create(['user_id'=>$user->id]);
        $this->assertTrue($organization->hasCompleteAccess());
        $service=app(OrganizationAgreementService::class);
        $request=$service->request($user,['project']);
        $request->update(['contract_file_path'=>'draft.pdf','signed_contract_file_path'=>'final.pdf','status'=>'1']);
        $this->assertNotNull($service->payload($request)['draft_url']);
        $this->assertNull($service->payload($request)['final_url']);
        $request->update(['status'=>'3']);
        $this->assertNotNull($service->payload($request)['final_url']);
        $sample=$this->makeOrder($user);
        $data=['address_id'=>$sample->user_address_id,'category_id'=>$sample->category_id,'total_price'=>0,'date'=>now()->toDateString(),'time'=>'10 - 12','platform'=>'android','description'=>str_repeat('note ',300),'steps'=>[],'delivery_mode'=>['once_short']];
        $result=app(OrderService::class)->submitOrder($data,$user->id);
        $this->assertTrue($result['success'],json_encode($result));
        $this->assertSame($data['description'],Order::latest('id')->first()->des);
        $data['delivery_mode']=['monthly_short'];
        $this->assertSame('AGREEMENT_REQUIRED',app(OrderService::class)->submitOrder($data,$user->id)['error_code']);
        Sanctum::actingAs($user);
        $this->postJson('/api/organization/contracts/upload')->assertForbidden();
    }

    public function test_receipt_and_admin_notes_include_escaped_free_text_from_all_fields(): void
    {
        $order=$this->makeOrder($this->makeUser(),['des'=>'summary <script>bad()</script>']);
        $detail=new \App\Models\OrderDetail(['user_descriptions'=>'hardware detail <img src=x onerror=bad()>']);
        $detail->setRelation('field',null); $detail->setRelation('fieldDetail',null);
        $order->setRelation('details',collect([$detail]));
        $html=view('partials.order-request-notes',['order'=>$order])->render();
        $this->assertStringContainsString('summary &lt;script&gt;',$html);
        $this->assertStringContainsString('hardware detail &lt;img',$html);
        $this->assertStringNotContainsString('<script>',$html);
        $this->assertStringNotContainsString('<img',$html);
    }
}
