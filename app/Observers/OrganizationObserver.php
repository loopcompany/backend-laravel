<?php

namespace App\Observers;

use App\Jobs\SendFirebaseNotificationJob;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;

class OrganizationObserver
{
    public function updated(Organization $organization): void
    {
        if (!config('firebase.enabled') || !$organization->user) {
            return;
        }

        if ($organization->wasChanged('contract_status')) {
            $notification = match ($organization->contract_status) {
                'approved' => [
                    'قرارداد سازمانی تأیید شد',
                    'قرارداد سازمانی شما تأیید شد.',
                    'organization_contract_approved',
                ],
                'rejected' => [
                    'قرارداد سازمانی رد شد',
                    'قرارداد سازمانی شما رد شد: ' . ($organization->contract_rejection_reason ?: 'لطفاً جزئیات را در اپلیکیشن بررسی کنید.'),
                    'organization_contract_rejected',
                ],
                default => null,
            };

            if ($notification) {
                $this->queue($organization, $notification[0], $notification[1], $notification[2]);
            }
        }

        if ($organization->wasChanged('profile_status')) {
            $notification = match ($organization->profile_status) {
                'approved' => [
                    'پروفایل سازمانی تأیید شد',
                    'پروفایل سازمانی شما تأیید شد.',
                    'organization_profile_approved',
                ],
                'rejected' => [
                    'پروفایل سازمانی رد شد',
                    'پروفایل سازمانی شما رد شد: ' . ($organization->profile_rejection_reason ?: 'لطفاً جزئیات را در اپلیکیشن بررسی کنید.'),
                    'organization_profile_rejected',
                ],
                default => null,
            };

            if ($notification) {
                $this->queue($organization, $notification[0], $notification[1], $notification[2]);
            }
        }
    }

    private function queue(Organization $organization, string $title, string $body, string $type): void
    {
        try {
            SendFirebaseNotificationJob::dispatch(
                $organization->user,
                $title,
                $body,
                [
                    'type' => $type,
                    'organization_id' => $organization->id,
                    'screen' => 'organization-profile',
                ]
            );
        } catch (\Throwable $exception) {
            Log::warning('Organization status push could not be queued.', [
                'organization_id' => $organization->id,
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
