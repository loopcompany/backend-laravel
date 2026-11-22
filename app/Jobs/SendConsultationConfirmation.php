<?php
namespace App\Jobs;
use App\Models\ProcurementConsultation;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
class SendConsultationConfirmation implements ShouldQueue {
 use Queueable;
 public int $tries=3;
 public array $backoff=[30,120,300];
 public function __construct(public ProcurementConsultation $consultation) {}
 public function handle(SmsService $sms):void {
  if (!config('smsir.enabled')) return;
  if (!$sms->sendText($this->consultation->user->phone,'کاربر گرامی، درخواست مشاوره تأمین کالا / محصول شما با موفقیت در لوپ ثبت شد. کارشناسان ما برای راهنمایی و انتخاب بهتر با شما تماس خواهند گرفت.')) throw new \RuntimeException('Consultation confirmation SMS failed');
 }
}
