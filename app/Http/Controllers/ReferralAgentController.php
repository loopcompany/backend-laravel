<?php

namespace App\Http\Controllers;

use App\Models\ReferralCommission;
use App\Models\ReferralWithdrawal;
use App\Services\ReferralAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * کیف پول مأمور خرید (سند اپ کاربر، بخش ۱۳-۲).
 */
class ReferralAgentController extends Controller
{
    public function __construct(private readonly ReferralAgentService $agents)
    {
    }

    /** GET /api/referral-agent/summary — برای کاربر غیرمأمور { is_agent: false } */
    public function summary(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->agents->summary($request->user())]);
    }

    /** GET /api/referral-agent/commissions?page=1 */
    public function commissions(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->is_referral_agent) {
            return $this->notAgent();
        }

        $this->agents->refreshPending($user);
        $page = ReferralCommission::query()->where('user_id', $user->id)->latest('id')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => collect($page->items())->map->toApiArray()->values(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /** GET /api/referral-agent/withdrawals */
    public function withdrawals(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->is_referral_agent) {
            return $this->notAgent();
        }

        return response()->json([
            'success' => true,
            'data' => ReferralWithdrawal::query()->where('user_id', $user->id)->latest('id')->limit(100)->get()->map->toApiArray()->values(),
        ]);
    }

    /** POST /api/referral-agent/withdrawals { amount, method: sheba|offset, sheba_number? } */
    public function storeWithdrawal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|integer|min:1',
            'method' => 'required|string|in:sheba,offset',
            'sheba_number' => 'nullable|string|max:40',
        ], [
            'amount.required' => 'مبلغ برداشت الزامی است.',
            'amount.integer' => 'مبلغ برداشت باید عدد صحیح (تومان) باشد.',
            'amount.min' => 'مبلغ برداشت نامعتبر است.',
            'method.required' => 'روش برداشت الزامی است.',
            'method.in' => 'روش برداشت نامعتبر است.',
        ]);

        $withdrawal = $this->agents->withdraw($request->user(), (int) $data['amount'], $data['method'], $data['sheba_number'] ?? null);

        return response()->json([
            'success' => true,
            'message' => $data['method'] === 'offset'
                ? 'مبلغ به کیف پول لوپ شما منتقل شد.'
                : 'درخواست برداشت ثبت شد و پس از بررسی واریز می‌شود.',
            'data' => $withdrawal->toApiArray(),
        ], 201);
    }

    private function notAgent(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'این بخش فقط برای مأمور خرید فعال است.',
            'error_code' => 'NOT_AGENT',
        ], 403);
    }
}
