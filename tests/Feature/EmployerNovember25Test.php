<?php
namespace Tests\Feature;
use App\Models\{User,Technician,ProcurementConsultation};
use App\Services\{CustomerProfileCompletion,RegistrationCodeService,TechnicianRegistrationService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Queue,Hash};
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;
class EmployerNovember25Test extends TestCase {
 use RefreshDatabase,MakesPeople;
 private function profile():array {return ['name'=>'علی','last_name'=>'رضایی','email'=>'profile@example.test','melicode'=>'1234567891','birth_date'=>'1370/01/01','city'=>'تهران','region'=>'08','postal_code'=>'1234567890','home_address'=>'تهران خیابان نمونه کوچه آزمایش پلاک ده واحد دو','home_number'=>'10','home_unit'=>'2','home_floor'=>'0'];}
 public function test_completion_requires_all_personal_and_address_sections_but_not_optional_work_or_bank_info():void {
  $user=$this->makeUser(); $service=app(CustomerProfileCompletion::class);
  $this->assertArrayHasKey('home_address',$service->missing($user));
  $this->assertArrayHasKey('home_number',$service->missing($user));
  $user->forceFill($this->profile())->save();
  $this->assertSame([],$service->missing($user));
  Queue::fake();$this->assertTrue($service->finish($user));
  $this->assertFalse($service->finish($user));
  $this->assertDatabaseCount('user_addresses',1);
  $this->assertDatabaseHas('user_addresses',['user_id'=>$user->id,'number'=>'10','floor'=>'0']);
 }
 public function test_old_session_is_blocked_until_a_new_login_after_profile_completion():void {
  Queue::fake();$user=$this->makeUser($this->profile());$old=$user->createToken('before')->accessToken;
  $this->freezeTime();app(CustomerProfileCompletion::class)->finish($user);
  $user->refresh()->withAccessToken($old);
  $this->assertArrayHasKey('profile_relogin',app(CustomerProfileCompletion::class)->orderErrors($user));
  $new=$user->createToken('after')->accessToken;$user->withAccessToken($new);
  $this->assertSame([],app(CustomerProfileCompletion::class)->orderErrors($user));
 }
 public function test_registration_code_is_numeric_and_uses_one_shared_sequence_for_users_and_technicians():void {
  $this->travelTo(now()->setDate(2026,11,17));$service=app(RegistrationCodeService::class);
  $user=$this->makeUser(['code'=>null]);
  $one=$service->issue($user,['city_code'=>'21','region_code'=>'08','method'=>1]);
  $tech=$this->makeTechnician(['referral_code'=>null]);
  $two=$service->issue($tech,['city_code'=>'21','region_code'=>'08','method'=>1,'staff_type'=>41]);
  $this->assertSame('2108056010000001',$one);$this->assertSame('2108054110000002',$two);
  $this->assertTrue(RegistrationCodeService::isIssued($one));$this->assertFalse(RegistrationCodeService::isIssued('2108056010000000'));
 }
 public function test_phone_verification_does_not_issue_technician_credentials():void {
  $tech=$this->makeTechnician(['approval_status'=>'pending','phone_verified_at'=>null,'phone_verify_code'=>Hash::make('123456'),'referral_code'=>null,'password'=>null]);
  $result=app(TechnicianRegistrationService::class)->verifyPhone($tech->phone,'123456');
  $this->assertTrue($result['success']);$this->assertFalse($result['data']['password_sent']);
  $this->assertNull($tech->fresh()->password);$this->assertNull($tech->fresh()->referral_code);
 }
 public function test_consultation_validates_budget_description_ownership_and_retries_only_enqueue_once():void {
  Queue::fake();$user=$this->makeUser($this->profile());Sanctum::actingAs($user);
  $payload=['client_request_id'=>'f924fc45-6a09-40cd-9f25-a6539e31a7f7','item_id'=>'laptop','new_count'=>0,'used_count'=>2,'description'=>'لپ تاپ برای کار اداری','budget_rial'=>1200000000];
  $this->postJson('/api/procurement-consultations',array_replace($payload,['budget_rial'=>0]))->assertUnprocessable()->assertJsonValidationErrors('budget_rial');
  $this->postJson('/api/procurement-consultations',array_replace($payload,['description'=>'']))->assertUnprocessable()->assertJsonValidationErrors('description');
  $first=$this->postJson('/api/procurement-consultations',$payload)->assertCreated();
  $this->postJson('/api/procurement-consultations',$payload)->assertCreated()->assertJsonPath('data.id',$first->json('data.id'));
  $this->assertDatabaseCount('procurement_consultations',1);
  Queue::assertPushed(\App\Jobs\SendConsultationConfirmation::class,1);
  $other=$this->makeTechnician();Sanctum::actingAs($other);
  $this->postJson('/api/procurement-consultations',$payload)->assertForbidden();
 }
 public function test_profile_email_cannot_be_changed_or_cleared_and_incomplete_edits_do_not_save():void {
  $user=$this->makeUser(['has_access'=>1,'email'=>'original@example.com']);Sanctum::actingAs($user);
  $this->putJson('/api/profile',['email'=>''])->assertUnprocessable()->assertJsonValidationErrors('email');
  $this->assertSame('original@example.com',$user->fresh()->email);
 }
 public function test_profile_address_sync_never_rewrites_an_address_used_by_an_existing_order():void {
  Queue::fake();$user=$this->makeUser($this->profile());$order=$this->makeOrder($user);
  $address=\App\Models\UserAddress::findOrFail($order->user_address_id);
  $address->update(['title'=>'منزل','address'=>'آدرس قدیمی سفارش که باید برای همیشه ثابت بماند']);
  $before=$address->address;app(CustomerProfileCompletion::class)->finish($user);
  $this->assertSame($before,$address->fresh()->address);$this->assertDatabaseCount('user_addresses',2);
  $user->forceFill(['home_address'=>'تهران خیابان جدید ساختمان تازه پلاک ده واحد دو'])->save();
  app(CustomerProfileCompletion::class)->finish($user);app(CustomerProfileCompletion::class)->finish($user);
  $this->assertSame($before,$address->fresh()->address);$this->assertDatabaseCount('user_addresses',3);
 }
 public function test_reinstall_registration_keeps_an_approved_technicians_credentials_and_offers_login():void {
  $technician=$this->makeTechnician(['phone_verified_at'=>now()]);$password=$technician->password;$code=$technician->referral_code;
  $result=app(TechnicianRegistrationService::class)->register(['phone'=>$technician->phone]);
  $this->assertTrue($result['success']);$this->assertTrue($result['data']['can_login']);$this->assertFalse($result['data']['pending_review']);$this->assertFalse($result['data']['verification_sent']);
  $this->assertSame($password,$technician->fresh()->password);$this->assertSame($code,$technician->fresh()->referral_code);
 }
}
