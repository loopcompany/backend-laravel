<?php
namespace App\Jobs;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
class SendProfileCompletionCode implements ShouldQueue {
 use Queueable;
 public int $tries=3;
 public array $backoff=[30,120,300];
 public function __construct(public User $user) {}
 public function handle(SmsService $sms): void {
  if (!config('smsir.enabled') || !$this->user->code) return;
  if (!$sms->sendUserCode($this->user->phone,$this->user->code)) throw new \RuntimeException('Registration code SMS delivery failed');
 }
}
