<?php
namespace App\Services;

use App\Jobs\SendManagedCode;
use App\Models\{CodeDelivery, ReferralCode, PromoCode, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CodeDeliveryService
{
    public function issue(ReferralCode|PromoCode $code, User $user, ?int $adminId, string $channel = 'sms', ?string $category = null): CodeDelivery
    {
        if (!in_array($channel, ['sms','phone','in_person'], true)) abort(422);
        if ($code instanceof ReferralCode && $code->policy_version && (int)$code->user_id !== (int)$user->id) {
            throw ValidationException::withMessages(['user_id'=>'کد فقط برای کاربر تعیین‌شده صادر می‌شود.']);
        }
        $delivery = CodeDelivery::create([
            'code_type'=>$code::class, 'code_id'=>$code->id, 'user_id'=>$user->id, 'admin_id'=>$adminId,
            'channel'=>$channel, 'recipient'=>$user->phone, 'category'=>$category,
            'status'=>$channel === 'sms' ? 'queued' : 'recorded', 'sent_at'=>$channel === 'sms' ? null : now(),
        ]);
        if ($channel === 'sms') {
            try { SendManagedCode::dispatch($delivery->id)->afterCommit(); }
            catch (\Throwable $e) { $delivery->update(['status'=>'failed','error_code'=>'QUEUE_UNAVAILABLE']); }
        }
        return $delivery;
    }

    public function send(int $id): void
    {
        $delivery = DB::transaction(function () use ($id) {
            $row = CodeDelivery::lockForUpdate()->findOrFail($id);
            if ($row->status !== 'queued') return null;
            $row->update(['status'=>'processing']);
            return $row;
        });
        if (!$delivery) return;
        if (!config('smsir.enabled')) {
            $delivery->update(['status'=>'disabled','error_code'=>'SMS_DISABLED']);
            return;
        }
        $model = match ($delivery->code_type) { ReferralCode::class=>ReferralCode::class, PromoCode::class=>PromoCode::class, default=>null };
        $code = $model ? $model::find($delivery->code_id) : null;
        if (!$code || !$delivery->recipient) {
            $delivery->update(['status'=>'failed','error_code'=>'RECIPIENT_OR_CODE_MISSING']);
            return;
        }
        $ok = app(SmsService::class)->sendManagedCode($delivery->recipient, $code->code, $code instanceof ReferralCode ? 'کد معرف' : 'کد تشویقی');
        $delivery->update(['status'=>$ok ? 'sent' : 'failed', 'sent_at'=>$ok ? now() : null, 'error_code'=>$ok ? null : 'PROVIDER_NOT_ACCEPTED']);
        if ($ok && $code instanceof ReferralCode && $code->status === ReferralCode::STATUS_ACTIVE) $code->update(['status'=>ReferralCode::STATUS_SENT]);
    }
}
