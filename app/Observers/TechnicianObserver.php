<?php

namespace App\Observers;

use App\Helpers\Helper;
use App\Jobs\SendFirebaseNotificationJob;
use App\Models\Technician;
use Illuminate\Support\Facades\Log;

class TechnicianObserver
{
    public function updated(Technician $technician): void
    {
        if (!$technician->wasChanged('approval_status')) {
            return;
        }

        $status = $technician->approval_status;
        $name = trim((string) $technician->name) ?: 'تکنسین';

        if ($technician->phone && in_array($status, [Technician::APPROVAL_APPROVED, Technician::APPROVAL_REJECTED], true)) {
            $templateId = config("smsir.technician_{$status}_template_id");

            if ($templateId) {
                try {
                    $smsData = [$name];
                    if ($status === Technician::APPROVAL_REJECTED) {
                        $smsData[] = (string) ($technician->rejection_reason ?: '');
                    }

                    Helper::send_sms(
                        $technician->phone,
                        $templateId,
                        $status === Technician::APPROVAL_REJECTED ? ['NAME', 'REASON'] : ['NAME'],
                        $smsData
                    );
                } catch (\Throwable $exception) {
                    Log::warning('Technician approval SMS could not be sent.', [
                        'technician_id' => $technician->id,
                        'status' => $status,
                        'error' => $exception->getMessage(),
                    ]);
                }
            } else {
                Log::warning('Technician approval SMS template is not configured.', [
                    'technician_id' => $technician->id,
                    'status' => $status,
                ]);
            }
        }

        if (!config('firebase.enabled')) {
            return;
        }

        $approved = $status === Technician::APPROVAL_APPROVED;

        try {
            SendFirebaseNotificationJob::dispatch(
                $technician,
                $approved ? 'تأیید ثبت‌نام تکنسین' : 'رد ثبت‌نام تکنسین',
                $approved
                    ? 'ثبت‌نام شما تأیید شد و می‌توانید وارد اپلیکیشن شوید.'
                    : 'ثبت‌نام شما رد شد. لطفاً دلیل رد را در پروفایل بررسی کنید.',
                [
                    'type' => $approved ? 'technician_approved' : 'technician_rejected',
                    'technician_id' => $technician->id,
                    'screen' => 'technician-profile',
                    'rejection_reason' => (string) ($technician->rejection_reason ?: ''),
                ]
            );
        } catch (\Throwable $exception) {
            Log::warning('Technician approval push could not be queued.', [
                'technician_id' => $technician->id,
                'status' => $status,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
