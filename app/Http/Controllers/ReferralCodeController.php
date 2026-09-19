<?php

namespace App\Http\Controllers;

use App\Models\ReferralCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralCodeController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $user = $request->user();
        $code = ReferralCode::normalize($validated['code']);

        return DB::transaction(function () use ($code, $user): JsonResponse {
            $managedCode = ReferralCode::with('user:id,name,last_name')
                ->whereRaw('UPPER(code) = ?', [$code])
                ->lockForUpdate()
                ->first();

            if (!$managedCode) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'کد معرف معتبر نیست.',
                    'error_code' => 'REFERRAL_CODE_NOT_FOUND',
                ]);
            }

            if (!$managedCode->user) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'این کد به کاربر معتبری اختصاص داده نشده است.',
                    'error_code' => 'REFERRAL_CODE_OWNER_NOT_FOUND',
                ]);
            }

            $alreadyUsedByCurrentUser =
                $managedCode->status === ReferralCode::STATUS_USED
                && (int) $managedCode->used_by_user_id === (int) $user->id;

            if (!in_array($managedCode->status, ReferralCode::usableStatuses(), true) && !$alreadyUsedByCurrentUser) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'این کد معرف قبلاً توسط کاربر دیگری استفاده شده است.',
                    'error_code' => 'REFERRAL_CODE_USED',
                    'data' => [
                        'code' => $managedCode->code,
                        'status' => $managedCode->status,
                        'status_label' => ReferralCode::statuses()[$managedCode->status] ?? $managedCode->status,
                    ],
                ]);
            }

            if (!$alreadyUsedByCurrentUser) {
                $managedCode->update([
                    'status' => ReferralCode::STATUS_USED,
                    'used_by_user_id' => $user->id,
                    'used_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'valid' => true,
                'message' => 'کد معرف با موفقیت ثبت شد.',
                'data' => [
                    'code' => $managedCode->code,
                    'status' => ReferralCode::STATUS_USED,
                    'status_label' => ReferralCode::statuses()[ReferralCode::STATUS_USED],
                    'discount_percent' => (int) $managedCode->discount_percent,
                    'source' => 'managed_referral_code',
                    'referrer' => [
                        'id' => $managedCode->user->id,
                        'name' => $managedCode->user->name,
                        'last_name' => $managedCode->user->last_name,
                    ],
                ],
            ]);
        });
    }
}
