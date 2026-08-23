<?php

namespace App\Http\Controllers;

use App\Services\UserGemDiscountService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserGemDiscountController extends Controller
{
    public function __construct(private UserGemDiscountService $service) {}

    /**
     * دریافت لیست تراکنش‌های gem کاربر
     */
    public function fetchGemTransactions(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthorized!'], 401);
        }

        $result = $this->service->getUserGemTransactions($request->user()->id);

        return response()->json($result['data'], 200);
    }

    /**
     * دریافت لیست کدهای تخفیف کاربر
     */
    public function fetchUserDiscounts(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthorized!'], 401);
        }

        $result = $this->service->getUserDiscounts($request->user()->id);

        return response()->json($result['data'], 200);
    }
}
