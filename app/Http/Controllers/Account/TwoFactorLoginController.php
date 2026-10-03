<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\LoginActivityService;
use App\Services\OrganizationRegistrationService;
use App\Services\Security\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * مرحله‌ی دوم ورود وقتی تأیید دومرحله‌ای فعال است.
 * POST /api/auth/two-factor/verify { two_factor_token, code } — کد بازیابی هم پذیرفته می‌شود.
 * پاسخ موفق همان قالب پاسخ ورود اصلی (کاربر یا سازمان) است.
 */
class TwoFactorLoginController extends Controller
{
    public function verify(
        Request $request,
        TwoFactorService $twoFactor,
        AuthService $authService,
        OrganizationRegistrationService $organizationService,
        LoginActivityService $loginActivityService,
    ): JsonResponse {
        $data = $request->validate([
            'two_factor_token' => 'required|string|max:128',
            'code' => 'required|string|max:20',
        ], [
            'two_factor_token.required' => 'شناسه‌ی مرحله‌ی دوم ورود الزامی است.',
            'code.required' => 'کد تأیید الزامی است.',
        ]);

        ['user' => $user, 'kind' => $kind] = $twoFactor->completeChallenge($data['two_factor_token'], $data['code']);

        if ($kind === 'organization') {
            $result = $organizationService->completeLogin($user);
            $this->logLogin($loginActivityService, 'organization', $user->id, $request);

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
            ]);
        }

        $result = $authService->completeLogin($user);
        $this->logLogin($loginActivityService, 'user', $user->id, $request);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data'],
        ]);
    }

    private function logLogin(LoginActivityService $service, string $type, int $userId, Request $request): void
    {
        try {
            $service->logLogin($type, $userId, $request);
        } catch (Throwable) {
            // لاگ ورود نباید ورود را خراب کند
        }
    }
}
