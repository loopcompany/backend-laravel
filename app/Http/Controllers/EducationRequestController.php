<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEducationRequestRequest;
use App\Services\EducationRequestService;
use Illuminate\Http\JsonResponse;

class EducationRequestController extends Controller
{
    public function __construct(
        private EducationRequestService $service
    ) {}

    /**
     * ثبت درخواست آموزش/مراجعه جدید
     * POST /api/technician/education-requests
     */
    public function store(StoreEducationRequestRequest $request): JsonResponse
    {
        $technician = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($technician instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $validated = $request->validated();
        
        $result = $this->service->createRequest(
            $technician->id,
            $validated['section'],
            $validated['description']
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'CREATE_ERROR'
            ], 400);
        }

        return response()->json($result, 201);
    }

    /**
     * دریافت لیست درخواست‌های تکنسین
     * GET /api/technician/education-requests
     */
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $technician = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($technician instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->service->getMyRequests($technician->id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'GET_ERROR'
            ], 400);
        }

        return response()->json($result, 200);
    }

    /**
     * دریافت جزئیات یک درخواست
     * GET /api/technician/education-requests/{id}
     */
    public function show(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $technician = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($technician instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->service->getRequestDetail($id, $technician->id);

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'REQUEST_NOT_FOUND' => 404,
                'UNAUTHORIZED_ACCESS' => 403,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'GET_ERROR'
            ], $statusCode);
        }

        return response()->json($result, 200);
    }
}
