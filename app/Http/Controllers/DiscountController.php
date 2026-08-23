<?php

namespace App\Http\Controllers;

use App\Services\DiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function __construct(
        private DiscountService $service
    ) {}

    /**
     * دریافت پیشنهادات هفتگی
     * 
     * @return JsonResponse
     */
    public function fetchOffers(): JsonResponse
    {
        $result = $this->service->getWeeklyOffers();
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }
        
        return response()->json($result['data']);
    }

    /**
     * دریافت دسته‌بندی‌های دارای تخفیف
     * 
     * @return JsonResponse
     */
    public function fetchDiscountCategories(): JsonResponse
    {
        $result = $this->service->getDiscountCategories();
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }
        
        return response()->json($result['data']);
    }

    /**
     * دریافت لیست تمام تخفیف‌ها
     * 
     * @return JsonResponse
     */
    public function fetchDiscounts(): JsonResponse
    {
        $result = $this->service->getAllDiscounts();
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }
        
        return response()->json($result['data']);
    }

    /**
     * دریافت تخفیف تایم‌دار فعال
     * 
     * @return JsonResponse
     */
    public function fetchDiscountTimed(): JsonResponse
    {
        $result = $this->service->getActiveTimedDiscount();
        
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }
        
        return response()->json($result['data']);
    }

    /**
     * دریافت جزئیات تخفیف
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchDiscountDetail(Request $request): JsonResponse
    {
        if (!$request->has('discountId')) {
            return response()->json([
                'success' => false,
                'message' => 'شناسه تخفیف الزامی است.',
                'error_code' => 'DISCOUNT_ID_REQUIRED'
            ], 400);
        }

        $result = $this->service->getDiscountDetail($request->input('discountId'));
        
        $statusCode = 200;
        if (!$result['success']) {
            $statusCode = ($result['error_code'] ?? '') == 'DISCOUNT_NOT_FOUND' ? 404 : 400;
        }
        
        return response()->json($result, $statusCode);
    }

    /**
     * دریافت کد تخفیف توسط کاربر
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getDiscount(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        if (!$request->has('discountId')) {
            return response()->json([
                'success' => false,
                'message' => 'شناسه تخفیف الزامی است.',
                'error_code' => 'DISCOUNT_ID_REQUIRED'
            ], 400);
        }

        $result = $this->service->claimDiscount($user->id, $request->input('discountId'));
        
        // تعیین status code بر اساس نوع خطا
        $statusCode = 200;
        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'INVALID_DISCOUNT_ID' => 404,
                'ALREADY_CLAIMED' => 409,
                'INSUFFICIENT_GEMS' => 403,
                default => 400
            };
        }
        
        return response()->json($result, $statusCode);
    }

    /**
     * بررسی اعتبار کد تخفیف
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkDiscount(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized!'
            ], 401);
        }

        // اعتبارسنجی ورودی
        $request->validate([
            'discountCode' => 'required|string',
            'categoryId' => 'required|integer'
        ]);

        $result = $this->service->validateDiscountCode(
            $request->input('discountCode'),
            $request->input('categoryId'),
            $user->id
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 409);
        }

        return response()->json([
            'message' => $result['message'],
            'discount_code_percent' => $result['data']['discount_percent']
        ], 200);
    }
}
