<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetExtraServicesRequest;
use App\Http\Requests\StoreExtraServicesRequest;
use App\Services\ExtraServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExtraServiceController extends Controller
{
    public function __construct(
        protected ExtraServiceService $extraServiceService
    ) {}

    /**
     * دریافت خدمات اضافی برای یک دسته‌بندی و سفارش
     * GET /api/technician/extra-services
     */
    public function getExtraServices(GetExtraServicesRequest $request): JsonResponse
    {
        $technician = $request->user();
        
        $result = $this->extraServiceService->getExtraServicesForOrder(
            categoryId: $request->category_id,
            orderId: $request->order_id,
            technicianId: $technician ? $technician->id : null
        );

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'UNAUTHORIZED_ACCESS' => 403,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']['extra_services'],
            'items' => $result['data']['order_items'],
        ]);
    }

    /**
     * ذخیره خدمات اضافی برای یک سفارش
     * POST /api/technician/post-extra-services
     */
    public function storeExtraServices(StoreExtraServicesRequest $request): JsonResponse
    {
        $technician = $request->user();
        
        $result = $this->extraServiceService->storeExtraServices(
            orderId: $request->order_id,
            extras: $request->extras,
            technicianId: $technician ? $technician->id : null
        );

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'UNAUTHORIZED_ACCESS' => 403,
                'EXTRA_NOT_FOUND' => 404,
                'EXTRA_DETAIL_NOT_FOUND' => 404,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ], 201);
    }

    /**
     * نمایش خدمات اضافی یک سفارش
     * GET /api/orders/{orderId}/extra-services
     */
    public function showOrderExtraServices(int $orderId, Request $request): JsonResponse
    {
        $user = $request->user();
        
        $result = $this->extraServiceService->showOrderExtraServices(
            orderId: $orderId,
            userId: $user ? $user->id : null
        );

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'UNAUTHORIZED_ACCESS' => 403,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }
}
