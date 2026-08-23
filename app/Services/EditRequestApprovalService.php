<?php

namespace App\Services;

use App\Models\EditRequest;
use App\Models\Organization;
use App\Models\Region;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditRequestApprovalService
{
    public function approve(EditRequest $record): void
    {
        DB::transaction(function () use ($record) {
            $editRequest = EditRequest::query()
                ->lockForUpdate()
                ->findOrFail($record->getKey());

            $this->ensureRequestIsPending($editRequest);

            $user = User::query()
                ->lockForUpdate()
                ->find($editRequest->user_id);

            if (!$user) {
                throw ValidationException::withMessages([
                    'user_id' => 'کاربر مربوط به این درخواست پیدا نشد.',
                ]);
            }

            $organization = Organization::query()
                ->lockForUpdate()
                ->find($editRequest->organization_id);

            if (!$organization) {
                throw ValidationException::withMessages([
                    'organization_id' => 'سازمان مربوط به این درخواست پیدا نشد.',
                ]);
            }

            $regionCode = filter_var(
                $editRequest->region,
                FILTER_VALIDATE_INT
            );

            if ($regionCode === false || $regionCode < 1 || $regionCode > 22) {
                throw ValidationException::withMessages([
                    'region' => 'کد منطقه باید عددی بین ۱ تا ۲۲ باشد.',
                ]);
            }

            $region = Region::query()
                ->where('code', $regionCode)
                ->first();

            if (!$region) {
                throw ValidationException::withMessages([
                    'region' => 'منطقه‌ای با این کد در جدول مناطق پیدا نشد.',
                ]);
            }

            $emailIsUsed = User::query()
                ->where('email', $editRequest->email)
                ->whereKeyNot($user->getKey())
                ->exists();

            if ($emailIsUsed) {
                throw ValidationException::withMessages([
                    'email' => 'این ایمیل قبلاً برای کاربر دیگری ثبت شده است.',
                ]);
            }

            /*
             * اطلاعات مربوط به جدول users
             */
            $user->forceFill([
                'profile_photo_path' => filled($editRequest->profile_image)
                    ? $editRequest->profile_image
                    : $user->profile_photo_path,

                'birth_date' => $editRequest->birth_date,
                'email' => $editRequest->email,
                'region' => $regionCode,
                'region_id' => $region->getKey(),
                'postal_code' => $editRequest->postal_code,
            ])->save();

            /*
             * اطلاعات مربوط به جدول organizations
             */
            $organization->forceFill([
                'organization_name' => $editRequest->organization_name,
                'business_name' => $editRequest->business_name,
                'manager_full_name' => $editRequest->manager_full_name,
                'agent_name' => $editRequest->agent_name,
                'agent_phone' => $editRequest->agent_phone,
                'history' => $editRequest->history,
                'organization_phone' => $editRequest->organization_phone,
                'organization_address' => $editRequest->organization_address,
            ])->save();

            /*
             * درخواست تأیید شده است.
             */
            $editRequest->forceFill([
                'status' => EditRequest::STATUS_APPROVED,
            ])->save();
        }, 3);
    }

    public function reject(EditRequest $record): void
    {
        DB::transaction(function () use ($record) {
            $editRequest = EditRequest::query()
                ->lockForUpdate()
                ->findOrFail($record->getKey());

            $this->ensureRequestIsPending($editRequest);

            $editRequest->forceFill([
                'status' => EditRequest::STATUS_REJECTED,
            ])->save();
        }, 3);
    }

    private function ensureRequestIsPending(EditRequest $editRequest): void
    {
        if (!$editRequest->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'این درخواست قبلاً بررسی شده و امکان پردازش مجدد آن وجود ندارد.',
            ]);
        }
    }
}