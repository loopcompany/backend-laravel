<?php
namespace Tests\Unit;
use App\Helpers\Helper;
use App\Services\TechnicianAdmissionService;
use Cryptommer\Smsir\Smsir;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

/** Exercise the actual admission and SMS boundary; no real message is sent. */
class TechnicianPasswordSMSTest extends TestCase
{
 use RefreshDatabase, MakesPeople;
 public function test_admission_sends_issued_code_then_fresh_password_and_preserves_it_on_repeat():void
 {
  config(['smsir.enabled'=>true,'smsir.technician_approved_template_id'=>null,'firebase.enabled'=>false]);
  $technician=$this->makeTechnician(['approval_status'=>'pending','phone_verified_at'=>now(),'referral_code'=>null,'melicode'=>'1234567891']);
  $technician->forceFill(['registration_method'=>3])->saveQuietly();
  $failure=null;\Illuminate\Support\Facades\Log::listen(function($event)use(&$failure){if(isset($event->context['exception_message']))$failure=$event->context['exception_message'];});
  $captured=null;$sender=\Mockery::mock(\Cryptommer\Smsir\Classes\Send::class);
  $sender->shouldReceive('Verify')->once()->withArgs(function($phone,$template,$parameters)use($technician,&$captured){
   $this->assertSame($technician->phone,$phone);$this->assertSame(812565,$template);
   $this->assertSame(['CODE','PASS'],array_map(fn($parameter)=>$parameter->Name,$parameters));
   $captured=array_map(fn($parameter)=>$parameter->Value,$parameters);return true;
  })->andReturn(new \Cryptommer\Smsir\Objects\VerifyResponse(['status'=>1,'message'=>'OK','data'=>['messageId'=>123,'cost'=>1]]));
  Smsir::shouldReceive('Send')->once()->andReturn($sender);
  $data=['city_code'=>'21','region_code'=>'08','staff_type'=>41,'method'=>1,'documents_confirmed'=>true];
  $service=app(TechnicianAdmissionService::class);$sent=$service->admit($technician,$data,null);$this->assertTrue($sent,(string)$failure);
  $technician->refresh();$this->assertTrue($technician->isApproved());$this->assertMatchesRegularExpression('/^[0-9]{16}$/',$technician->referral_code);
  $this->assertSame('3', $technician->referral_code[8]);
  $this->assertSame($technician->referral_code,$captured[0]);$this->assertGreaterThanOrEqual(12,strlen($captured[1]));
  $this->assertTrue(Hash::check($captured[1],$technician->password));$hash=$technician->password;
  $this->assertThrows(fn()=>$service->admit($technician,$data,null),ValidationException::class);
  $this->assertSame($hash,$technician->fresh()->password);
 }
 public function test_missing_confirmation_cannot_approve_or_issue_credentials():void
 {
  $technician=$this->makeTechnician(['approval_status'=>'pending','referral_code'=>null]);
  $this->assertThrows(fn()=>app(TechnicianAdmissionService::class)->admit($technician,[],null),ValidationException::class);
  $this->assertSame('pending',$technician->fresh()->approval_status);$this->assertNull($technician->fresh()->referral_code);
 }
 public function test_sms_failure_objects_are_false_rather_than_truthy_success():void
 {
  config(['smsir.enabled'=>true]);$sender=\Mockery::mock(\Cryptommer\Smsir\Classes\Send::class);$sender->shouldReceive('Verify')->once()->andReturn(new \Cryptommer\Smsir\Objects\VerifyResponse(['status'=>0,'message'=>'Rejected','data'=>['messageId'=>0,'cost'=>0]]));
  Smsir::shouldReceive('Send')->once()->andReturn($sender);
  $this->assertFalse(Helper::send_sms('09000000000','812565',['CODE','PASS'],['test-code','test-password']));
 }
}
