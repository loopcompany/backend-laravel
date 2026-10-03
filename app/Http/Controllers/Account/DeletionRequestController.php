<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Security\AccountDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * درخواست حذف حساب کاربری (بخش ۷-۸).
 */
class DeletionRequestController extends Controller
{
    public function __construct(private readonly AccountDeletionService $deletions)
    {
    }

    /** GET /api/account/deletion-request */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->deletions->current($request->user())?->toApiArray(),
        ]);
    }

    /** POST /api/account/deletion-request/send-code */
    public function sendCode(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->deletions->sendCode($request->user())]);
    }

    /** POST /api/account/deletion-request { code, reason? } */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:10',
            'reason' => 'nullable|string|max:1000',
        ], [
            'code.required' => 'کد تأیید الزامی است.',
            'reason.max' => 'دلیل حذف حداکثر ۱۰۰۰ کاراکتر است.',
        ]);

        $deletion = $this->deletions->request($request->user(), $data['code'], $data['reason'] ?? null, $request);

        return response()->json([
            'success' => true,
            'message' => $deletion->status === 'pending'
                ? 'درخواست حذف حساب ثبت شد و پس از تأیید پنل مدیریت انجام می‌شود.'
                : 'درخواست حذف حساب ثبت شد. تا قبل از تاریخ اجرا می‌توانید آن را لغو کنید.',
            'data' => $deletion->toApiArray(),
        ]);
    }

    /** POST /api/account/deletion-request/cancel */
    public function cancel(Request $request): JsonResponse
    {
        $deletion = $this->deletions->cancel($request->user());

        return response()->json([
            'success' => true,
            'message' => 'درخواست حذف حساب لغو شد.',
            'data' => $deletion->toApiArray(),
        ]);
    }
}
