<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationApproved
{
    /**
     * Handle an incoming request.
     * 
     * این middleware چک می‌کند که آیا کاربر سازمانی تایید شده است یا خیر
     * کاربران فردی (user_type !== 'organization') نیاز به این middleware ندارند
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // اگر کاربر لاگین نکرده
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'لطفا ابتدا وارد حساب کاربری خود شوید.',
                'error_code' => 'UNAUTHENTICATED'
            ], 401);
        }

        // اگر کاربر فردی است، بدون محدودیت ادامه بده
        if (!$user->isOrganization()) {
            return $next($request);
        }

        // برای کاربران سازمانی، چک کردن وضعیت تایید
        $organization = $user->organization;

        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات سازمان یافت نشد.',
                'error_code' => 'ORGANIZATION_NOT_FOUND'
            ], 404);
        }

        // چک کردن دسترسی کامل
        $hasCompleteAccess = $organization->hasCompleteAccess();

        if (!$hasCompleteAccess) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی محدود: لطفا منتظر تایید ادمین باشید',
                'error_code' => 'ACCESS_RESTRICTED',
                'data' => [
                    'profile_status' => $organization->profile_status,
                    'contract_status' => $organization->contract_status,
                    'profile_status_label' => $organization->profile_status_label,
                    'contract_status_label' => $organization->contract_status_label,
                    'profile_rejection_reason' => $organization->profile_rejection_reason,
                    'contract_rejection_reason' => $organization->contract_rejection_reason,
                    'allowed_screens' => [
                        'OrganizationProfile',
                        'OrganizationContract',
                        'AccountSettings'
                    ],
                    'blocked_message' => $this->getBlockedMessage($organization),
                ]
            ], 403);
        }

        return $next($request);
    }

    /**
     * دریافت پیام محدودیت بر اساس وضعیت
     */
    private function getBlockedMessage($organization): string
    {
        if ($organization->isProfileRejected()) {
            return 'پروفایل شما رد شده است. لطفا اطلاعات خود را اصلاح کنید.';
        }

        if ($organization->isContractRejected()) {
            return 'قرارداد شما رد شده است. لطفا مجددا قرارداد را آپلود کنید.';
        }

        if ($organization->isContractNotUploaded()) {
            return 'لطفا قرارداد امضا شده را آپلود کنید.';
        }

        if ($organization->isProfilePending() || $organization->isContractPending()) {
            return 'اطلاعات شما در حال بررسی توسط ادمین است. لطفا منتظر بمانید.';
        }

        return 'دسترسی محدود است.';
    }
}
