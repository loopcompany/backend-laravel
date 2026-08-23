<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFaultReportRequest;
use App\Services\FaultReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaultReportController extends Controller
{
    public function __construct(
        private FaultReportService $service
    ) {}

    /**
     * ثبت گزارش خرابی جدید
     * 
     * @param StoreFaultReportRequest $request
     * @return JsonResponse
     */
    public function store(StoreFaultReportRequest $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->service->createFaultReport($user->id, $request->validated());

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * دریافت لیست گزارش‌های خرابی کاربر
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->service->getUserFaultReports($user->id);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * دریافت جزئیات یک گزارش خرابی
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->service->getFaultReportDetail($user->id, $id);

        $statusCode = match ($result['error_code'] ?? '') {
            'FAULT_REPORT_NOT_FOUND' => 404,
            'UNAUTHORIZED' => 403,
            default => ($result['success'] ? 200 : 400)
        };

        return response()->json($result, $statusCode);
    }
}
