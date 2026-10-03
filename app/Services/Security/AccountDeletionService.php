<?php

namespace App\Services\Security;

use App\Exceptions\AccountSecurityException;
use App\Models\AccountDeletionRequest;
use App\Models\AccountKnownDevice;
use App\Models\AccountSecuritySetting;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * حذف حساب کاربری (بخش ۷-۸).
 *
 * - حساب عادی: درخواست مستقیم «تأیید شده» با ۱۴ روز مهلت انصراف؛ بعد از آن خودکار اجرا می‌شود.
 * - حساب سازمانی/شرکتی: «در انتظار» تا پنل ادمین تأیید کند؛ با تأیید ادمین بلافاصله اجرا می‌شود.
 * - اجرا = ناشناس‌سازی اطلاعات شخصی و بستن دسترسی. سفارش‌ها و سوابق مالی حذف نمی‌شوند (بند ۱۳ حریم خصوصی).
 */
class AccountDeletionService
{
    public const GRACE_DAYS = 14;

    public function __construct(
        private readonly SecurityCodeService $codes,
        private readonly AccountSecurityLogger $logger,
    ) {
    }

    public function current(User $user): ?AccountDeletionRequest
    {
        return AccountDeletionRequest::where('user_id', $user->id)->latest('id')->first();
    }

    public function sendCode(User $user): array
    {
        return $this->codes->send($user, 'account-deletion', $user->phone);
    }

    public function request(User $user, string $code, ?string $reason, ?Request $request = null): AccountDeletionRequest
    {
        if ($this->openRequest($user)) {
            throw new AccountSecurityException('درخواست حذف حساب شما قبلاً ثبت شده است.', 'ALREADY_REQUESTED', 422);
        }

        $this->codes->verify($user, 'account-deletion', $code);

        $isOrganization = $user->isOrganization();

        $deletion = AccountDeletionRequest::create([
            'user_id' => $user->id,
            'account_kind' => $isOrganization ? AccountDeletionRequest::KIND_ORGANIZATION : AccountDeletionRequest::KIND_INDIVIDUAL,
            'status' => $isOrganization ? AccountDeletionRequest::STATUS_PENDING : AccountDeletionRequest::STATUS_APPROVED,
            'reason' => $reason,
            'phone_snapshot' => $user->phone,
            'name_snapshot' => trim($user->name . ' ' . $user->last_name),
            'requested_at' => now(),
            'scheduled_at' => $isOrganization ? null : now()->addDays(self::GRACE_DAYS),
        ]);

        $this->logger->activity($user, 'deletion_requested', $request, meta: ['request_id' => $deletion->id]);

        return $deletion;
    }

    public function cancel(User $user): AccountDeletionRequest
    {
        $deletion = $this->openRequest($user);

        if (!$deletion) {
            throw new AccountSecurityException('درخواست حذف فعالی برای لغو وجود ندارد.', 'NO_OPEN_REQUEST', 422);
        }

        $deletion->update(['status' => AccountDeletionRequest::STATUS_CANCELED, 'canceled_at' => now()]);

        return $deletion;
    }

    /** تأیید ادمین (برای حساب سازمانی): بلافاصله اجرا می‌شود. */
    public function approveByAdmin(AccountDeletionRequest $deletion, ?Admin $admin, ?string $note = null): void
    {
        if ($deletion->status !== AccountDeletionRequest::STATUS_PENDING) {
            throw new AccountSecurityException('فقط درخواست‌های «در انتظار» قابل تأیید هستند.', 'NOT_PENDING', 422);
        }

        $deletion->update([
            'status' => AccountDeletionRequest::STATUS_APPROVED,
            'scheduled_at' => now(),
            'reviewed_by' => $admin?->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);

        $this->execute($deletion->fresh());
    }

    public function rejectByAdmin(AccountDeletionRequest $deletion, ?Admin $admin, ?string $note = null): void
    {
        if ($deletion->status !== AccountDeletionRequest::STATUS_PENDING) {
            throw new AccountSecurityException('فقط درخواست‌های «در انتظار» قابل رد هستند.', 'NOT_PENDING', 422);
        }

        $deletion->update([
            'status' => AccountDeletionRequest::STATUS_REJECTED,
            'reviewed_by' => $admin?->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);
    }

    /** درخواست‌های تأییدشده‌ای که مهلتشان تمام شده را اجرا می‌کند؛ تعداد اجراشده را برمی‌گرداند. */
    public function processDue(): int
    {
        $count = 0;

        AccountDeletionRequest::where('status', AccountDeletionRequest::STATUS_APPROVED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->each(function (AccountDeletionRequest $deletion) use (&$count) {
                $this->execute($deletion);
                $count++;
            });

        return $count;
    }

    /** ناشناس‌سازی اطلاعات شخصی و بستن دسترسی؛ سفارش‌ها، تراکنش‌ها و گزارش‌ها می‌مانند. */
    public function execute(AccountDeletionRequest $deletion): void
    {
        if ($deletion->status !== AccountDeletionRequest::STATUS_APPROVED) {
            return;
        }

        DB::transaction(function () use ($deletion) {
            $user = User::find($deletion->user_id);

            if ($user) {
                $user->tokens()->delete();
                $user->firebaseDeviceTokens()->delete();

                $user->forceFill([
                    'name' => 'کاربر حذف‌شده',
                    'last_name' => null,
                    'phone' => 'deleted-' . $user->id . '-' . now()->timestamp,
                    'email' => null,
                    'melicode' => null,
                    'birth_date' => null,
                    'mobile_number' => null,
                    'phone_number' => null,
                    'postal_code' => null,
                    'home_address' => null,
                    'work_address' => null,
                    'card_number' => null,
                    'sheba_number' => null,
                    'profile_photo_path' => null,
                    'password' => null,
                    'has_access' => 0,
                ])->save();

                // آدرس‌ها به سفارش‌ها وصل‌اند و حذف نمی‌شوند؛ فقط اطلاعات تماس شخصی پاک می‌شود
                DB::table('user_addresses')->where('user_id', $user->id)->update([
                    'fname' => null, 'lname' => null, 'mobile' => null, 'telephone' => null,
                ]);

                if ($user->organization) {
                    $user->organization->forceFill([
                        'agent_name' => null,
                        'agent_phone' => null,
                        // ستون NOT NULL و یکتا (۱۰ کاراکتر) است؛ جایگزین غیرعددی با کد ملی واقعی تداخل ندارد
                        'manager_national_code' => 'D' . str_pad((string) $user->organization->id, 9, '0', STR_PAD_LEFT),
                    ])->save();
                }

                AccountSecuritySetting::where('account_type', $user->getMorphClass())->where('account_id', $user->id)->delete();
                AccountKnownDevice::where('account_type', $user->getMorphClass())->where('account_id', $user->id)->delete();
            }

            $deletion->update([
                'status' => AccountDeletionRequest::STATUS_DONE,
                'executed_at' => now(),
            ]);
        });
    }

    private function openRequest(User $user): ?AccountDeletionRequest
    {
        return AccountDeletionRequest::where('user_id', $user->id)
            ->whereIn('status', [AccountDeletionRequest::STATUS_PENDING, AccountDeletionRequest::STATUS_APPROVED])
            ->latest('id')
            ->first();
    }
}
