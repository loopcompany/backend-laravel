<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Security\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * مدیریت تأیید دومرحله‌ای (بخش ۷-۱).
 */
class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor)
    {
    }

    /** POST /api/account/security/two-factor/send-code { purpose, method } */
    public function sendCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'purpose' => 'required|string|in:enable,disable,recovery_codes',
            'method' => 'nullable|string|in:sms,app',
        ], $this->messages());

        $result = $this->twoFactor->sendCode($request->user(), $data['purpose'], $data['method'] ?? 'sms');

        return response()->json(['success' => true, 'data' => $result]);
    }

    /** POST /api/account/security/two-factor/enable { method, code } */
    public function enable(Request $request): JsonResponse
    {
        $data = $request->validate([
            'method' => 'required|string|in:sms,app',
            'code' => 'required|string|max:20',
        ], $this->messages());

        $result = $this->twoFactor->enable($request->user(), $data['method'], $data['code'], $request);

        return response()->json([
            'success' => true,
            'message' => 'تأیید دومرحله‌ای فعال شد. کدهای بازیابی را در جای امنی نگه دارید.',
            'data' => $result,
        ]);
    }

    /** POST /api/account/security/two-factor/disable { code } */
    public function disable(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => 'required|string|max:20'], $this->messages());

        $this->twoFactor->disable($request->user(), $data['code'], $request);

        return response()->json(['success' => true, 'message' => 'تأیید دومرحله‌ای غیرفعال شد.']);
    }

    /** POST /api/account/security/two-factor/recovery-codes { code } */
    public function recoveryCodes(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => 'required|string|max:20'], $this->messages());

        $result = $this->twoFactor->regenerateRecoveryCodes($request->user(), $data['code'], $request);

        return response()->json([
            'success' => true,
            'message' => 'کدهای بازیابی جدید ساخته شد و کدهای قبلی باطل شدند.',
            'data' => $result,
        ]);
    }

    private function messages(): array
    {
        return [
            'purpose.required' => 'نوع درخواست الزامی است.',
            'purpose.in' => 'نوع درخواست نامعتبر است.',
            'method.required' => 'روش تأیید الزامی است.',
            'method.in' => 'روش تأیید باید پیامک یا اپ احراز هویت باشد.',
            'code.required' => 'کد تأیید الزامی است.',
        ];
    }
}
