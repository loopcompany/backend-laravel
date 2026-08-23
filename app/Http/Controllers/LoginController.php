<?php

namespace App\Http\Controllers;

use App\DTOs\LoginDTO;
use App\Http\Requests\LoginRequest;
use App\Models\Region;
use App\Services\AuthService;
use App\Services\LoginActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected LoginActivityService $loginActivityService
    ) {
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Create DTO from validated request data
            $dto = LoginDTO::fromArray($request->validated());

            // Authenticate user
            $result = $this->authService->login($dto);

            if ($result['success']) {
                // Log login activity (non-blocking)
                try {
                    $this->loginActivityService->logLogin('user', $result['data']['user']['id'], $request);
                } catch (\Exception $e) {
                    // Silently fail - don't disrupt login
                }

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data']
                ], 200);
            }

            $statusCode = isset($result['requires_verification']) ? 403 : 401;

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'requires_verification' => $result['requires_verification'] ?? false,
            ], $statusCode);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ورود. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر احراز هویت نشده است.',
                ], 401);
            }

            // Delete current token
            $request->user()->currentAccessToken()->delete();

            // Log logout activity (non-blocking)
            try {
                $this->loginActivityService->logLogout('user', $user->id, $request, 'logout');
            } catch (\Exception $e) {
                // Silently fail - don't disrupt logout
            }

            return response()->json([
                'success' => true,
                'message' => __("Exit was successful."),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در خروج. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout from all devices
     */
    public function logoutFromAllDevices(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر احراز هویت نشده است.',
                ], 401);
            }

            $result = $this->authService->logoutFromAllDevices($user->id);

            if ($result['success']) {
                // Log logout_all activity (non-blocking)
                try {
                    $this->loginActivityService->logLogout('user', $user->id, $request, 'logout_all');
                } catch (\Exception $e) {
                    // Silently fail - don't disrupt logout
                }

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در خروج از همه دستگاه‌ها. لطفاً مجدداً تلاش کنید.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated user info
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر احراز هویت نشده است.',
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'last_name' => $user->last_name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'phone_verified_at' => $user->phone_verified_at?->toISOString(),
                        'referral_code' => $user->referral_code,
                        'other_referral_code' => $user->other_referral_code,
                        'wallet' => $user->wallet,
                        'total_gems' => $user->total_gems, // ✅ مجموع امتیازات
                        'apple_check' => 0, // ✅ وضعیت بررسی اپل
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اطلاعات کاربر.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate token and return user info
     */
    public function validateToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'توکن نامعتبر یا منقضی شده است.',
                    'valid' => false,
                ], 401);
            }
            $user->loadSum('gemTransactions as user_gems', 'gems');


            // Check if user has access
            if (!$user->hasAccess()) {
                return response()->json([
                    'success' => false,
                    'message' => 'دسترسی کاربر مسدود شده است.',
                    'valid' => false,
                ], 403);
            }

            // Check if phone is verified
            if (!$user->isPhoneVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'شماره موبایل کاربر تایید نشده است.',
                    'valid' => false,
                    'requires_verification' => true,
                ], 403);
            }

            $region_info = Region::find($user->region_id);
            $organization_name = '';
            if ($user->account_type != 'individual') {
                if($user->organization){
                    $organization_name = $user->organization->organization_name;
                }
            }
            return response()->json([
                'success' => true,
                'message' => 'توکن معتبر است.',
                'valid' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'last_name' => $user->last_name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'melicode' => $user->melicode,
                        'phone_verified_at' => $user->phone_verified_at?->toISOString(),
                        'email_verified_at' => $user->email_verified_at?->toISOString(),
                        'referral_code' => $user->referral_code,
                        'other_referral_code' => $user->other_referral_code,
                        'wallet' => $user->wallet,
                        'has_access' => $user->hasAccess(),
                        'is_phone_verified' => $user->isPhoneVerified(),
                        'created_at' => $user->created_at?->toISOString(),
                        'updated_at' => $user->updated_at?->toISOString(),
                        'code' => $user->code,
                        'account_type' => $user->account_type,
                        'user_gems' => $user->user_gems,
                        'region' => $user->region,
                        'region_id' => $region_info,
                        'apple_check' => 0,
                        'organization_name'=> $organization_name
                    ],
                    'token_info' => [
                        'current_token_id' => $request->user()->currentAccessToken()->id ?? null,
                        'validated_at' => now()->toISOString(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی توکن.',
                'valid' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}