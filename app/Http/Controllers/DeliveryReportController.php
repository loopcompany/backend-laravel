<?php

namespace App\Http\Controllers;

use App\DTOs\DeliveryReportDTO;
use App\Http\Requests\StoreDeliveryReportRequest;
use App\Http\Requests\UpdateDeliveryReportRequest;
use App\Http\Requests\VerifyDeliveryReportRequest;
use App\Services\DeliveryReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryReportController extends Controller
{
    public function __construct(
        private DeliveryReportService $service
    ) {}

    /**
     * ثبت گزارش تحویل جدید توسط تکنسین
     */
    public function store(StoreDeliveryReportRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $data = $request->validated();
        $data['technician_id'] = $user->id;

        $dto = DeliveryReportDTO::fromArray($data);
        $result = $this->service->createReport($dto);

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'REPORT_ALREADY_EXISTS' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'CREATE_REPORT_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ], 201);
    }

    /**
     * به‌روزرسانی گزارش تحویل توسط تکنسین
     */
    public function update(UpdateDeliveryReportRequest $request, int $reportId): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->service->updateReport($reportId, $user->id, $request->validated());

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'REPORT_NOT_FOUND' => 404,
                'UNAUTHORIZED' => 403,
                'REPORT_ALREADY_VERIFIED' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'UPDATE_REPORT_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ], 200);
    }

    /**
     * دریافت گزارش تحویل برای یک سفارش خاص (تکنسین)
     */
    public function showByOrder(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->service->getReportByOrder($user->id, $orderId);

        if (!$result['success']) {
            $statusCode = ($result['error_code'] ?? '') == 'REPORT_NOT_FOUND' ? 404 : 400;

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'GET_REPORT_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ], 200);
    }

    /**
     * دریافت لیست تمام گزارش‌های تحویل تکنسین
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->service->getTechnicianReports($user->id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'GET_REPORTS_ERROR'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ], 200);
    }

    /**
     * تایید گزارش تحویل توسط کاربر
     */
    public function verifyByUser(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر عادی است (نه تکنسین)
        if ($user instanceof \App\Models\Technician) {
            return response()->json([
                'success' => false,
                'message' => 'این API فقط برای کاربران عادی است.',
            ], 403);
        }

        $result = $this->service->verifyReportByUser($orderId, $user->id);

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'REPORT_NOT_FOUND' => 404,
                'REPORT_ALREADY_VERIFIED' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'VERIFY_REPORT_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message']
        ], 200);
    }

    /**
     * دریافت گزارش تحویل برای کاربر (بر اساس orderId)
     */
    public function showForUser(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر عادی است
        if ($user instanceof \App\Models\Technician) {
            return response()->json([
                'success' => false,
                'message' => 'این API فقط برای کاربران عادی است.',
            ], 403);
        }

        // بررسی اینکه سفارش به کاربر تعلق دارد
        $orderRepo = app(\App\Repositories\OrderRepository::class);
        $order = $orderRepo->getUserOrder($user->id, $orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'سفارش یافت نشد یا به شما تعلق ندارد.',
                'error_code' => 'ORDER_NOT_FOUND'
            ], 404);
        }

        // دریافت گزارش تحویل
        $reportRepo = app(\App\Repositories\DeliveryReportRepository::class);
        $report = $reportRepo->findByOrderId($orderId);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'گزارش تحویل یافت نشد.',
                'error_code' => 'REPORT_NOT_FOUND'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'report' => $report
            ]
        ], 200);
    }

    /**
     * تایید گزارش تحویل توسط تکنسین با کد تایید
     */
    public function verifyWithCode(VerifyDeliveryReportRequest $request, int $orderId): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->service->verifyReportWithCode($orderId, $user->id, $request->input('code'));

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'REPORT_NOT_FOUND' => 404,
                'REPORT_ALREADY_VERIFIED' => 409,
                'INVALID_VERIFICATION_CODE' => 400,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'VERIFY_REPORT_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message']
        ], 200);
    }

    /**
     * ارسال مجدد کد تایید به کاربر
     */
    public function resendVerificationCode(int $orderId): JsonResponse
    {
        $user = request()->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->service->resendVerificationCode($orderId, $user->id);

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'REPORT_NOT_FOUND' => 404,
                'REPORT_ALREADY_VERIFIED' => 409,
                'UNAUTHORIZED' => 403,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'RESEND_CODE_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message']
        ], 200);
    }
}
