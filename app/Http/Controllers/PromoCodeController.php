<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }

    /**
     * Check a new admin-created promo code without consuming it.
     * POST /api/promo-codes/check
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $user = $request->user();

        $result = $this->orderService->checkPromoCode($validated['code'], $user->id);
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'],
            ], $result['error_code'] === 'PROMO_CODE_NOT_FOUND' ? 404 : 409);
        }

        return response()->json([
            'success' => true,
            'valid' => true,
            'message' => $result['message'],
            'data' => $result['data'],
        ]);
    }
}
