<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Services\TechnicianOrganizationOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TechnicianOrganizationOrderController extends Controller
{
    public function __construct(
        private TechnicianOrganizationOrderService $service
    ) {}

    /**
     * دریافت لیست سازمان‌هایی که به تکنسین سفارش داده‌اند
     * شامل تعداد سفارش هر سازمان و تعداد کل سفارشات سازمانی
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrganizations(Request $request): JsonResponse
    {
        $technician = auth('sanctum')->user();

        if (!$technician instanceof Technician) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است یا دسترسی ندارد.'
            ], 401);
        }

        $result = $this->service->getOrganizationsWithOrderCount($technician->id);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result['data']
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'error_code' => $result['error_code']
        ], 400);
    }

    /**
     * دریافت سفارشات یک سازمان خاص برای تکنسین
     *
     * @param Request $request
     * @param int $organizationId
     * @return JsonResponse
     */
    public function getOrganizationOrders(Request $request, int $organizationId): JsonResponse
    {
        $technician = auth('sanctum')->user();

        if (!$technician instanceof Technician) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است یا دسترسی ندارد.'
            ], 401);
        }

        $result = $this->service->getOrganizationOrders($technician->id, $organizationId);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? null,
                'data' => $result['data']
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'error_code' => $result['error_code']
        ], 400);
    }
}
