<?php

namespace App\Http\Controllers\Account;

use App\Exceptions\AccountSecurityException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Security\AccountSecurityLogger;
use App\Services\Security\SecurityCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * تأیید شماره موبایل فعلی و تغییر شماره موبایل (بخش ۷-۶).
 */
class MobileController extends Controller
{
    public function __construct(
        private readonly SecurityCodeService $codes,
        private readonly AccountSecurityLogger $logger,
    ) {
    }

    /** POST /api/account/mobile/send-code — بدنه‌ی خالی برای شماره‌ی فعلی، یا { mobile } برای تغییر شماره */
    public function sendCode(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate($this->mobileRules(), $this->messages());

        if (empty($data['mobile'])) {
            $result = $this->codes->send($user, 'mobile-verify', $user->phone);
        } else {
            $this->ensureCanChangeTo($user, $data['mobile']);
            // کد به شماره‌ی جدید فرستاده می‌شود تا مالکیت آن ثابت شود
            $result = $this->codes->send($user, 'mobile-change', $data['mobile'], ['mobile' => $data['mobile']]);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /** POST /api/account/mobile/verify { code } یا { code, mobile } */
    public function verify(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate($this->mobileRules() + ['code' => 'required|string|max:10'], $this->messages());

        if (empty($data['mobile'])) {
            $this->codes->verify($user, 'mobile-verify', $data['code']);
            $user->forceFill(['phone_verified_at' => now()])->save();

            return response()->json(['success' => true, 'message' => 'شماره موبایل تأیید شد.']);
        }

        $this->ensureCanChangeTo($user, $data['mobile']);
        $this->codes->verify($user, 'mobile-change', $data['code'], ['mobile' => $data['mobile']]);

        $oldPhone = $user->phone;
        DB::transaction(function () use ($user, $data) {
            $user->forceFill(['phone' => $data['mobile'], 'phone_verified_at' => now()])->save();
        });

        $this->logger->activity($user, 'mobile_changed', $request, meta: [
            'from' => self::mask($oldPhone),
            'to' => self::mask($data['mobile']),
        ]);
        $this->logger->alert($user, 'mobile_changed', $request);

        return response()->json([
            'success' => true,
            'message' => 'شماره موبایل با موفقیت تغییر کرد.',
            'data' => ['mobile' => $user->phone],
        ]);
    }

    private function ensureCanChangeTo(User $user, string $mobile): void
    {
        if ($mobile === $user->phone) {
            throw new AccountSecurityException('شماره‌ی جدید با شماره‌ی فعلی یکسان است.', 'SAME_MOBILE', 422);
        }

        if (User::where('phone', $mobile)->whereKeyNot($user->id)->exists()) {
            throw new AccountSecurityException('این شماره موبایل متعلق به حساب دیگری است.', 'MOBILE_TAKEN', 422);
        }
    }

    private function mobileRules(): array
    {
        return ['mobile' => ['nullable', 'string', 'regex:/^09[0-9]{9}$/']];
    }

    private function messages(): array
    {
        return [
            'mobile.regex' => 'شماره موبایل باید با 09 شروع شده و 11 رقم باشد.',
            'code.required' => 'کد تأیید الزامی است.',
        ];
    }

    private static function mask(?string $phone): ?string
    {
        return $phone ? substr($phone, 0, 4) . '***' . substr($phone, -4) : null;
    }
}
