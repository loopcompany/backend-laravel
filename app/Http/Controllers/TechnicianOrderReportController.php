<?php

namespace App\Http\Controllers;

use App\DTOs\TechnicianOrderReportDTO;
use App\DTOs\UpdateOrderLoopInfoDTO;
use App\DTOs\SetTechnicianDescriptionDTO;
use App\Http\Requests\StoreTechnicianOrderReportRequest;
use App\Http\Requests\ConfirmTechnicianOrderReportRequest;
use App\Http\Requests\UpdateOrderLoopInfoRequest;
use App\Http\Requests\SetTechnicianDescriptionRequest;
use App\Services\TechnicianOrderReportService;
use Illuminate\Http\Request;

class TechnicianOrderReportController extends Controller
{
    public function __construct(
        protected TechnicianOrderReportService $reportService
    ) {}

    /**
     * ثبت گزارش جدید توسط تکنسین
     * POST /api/technician/order-reports
     */
    public function store(StoreTechnicianOrderReportRequest $request)
    {
        $technician = $request->user();
        
        $dto = TechnicianOrderReportDTO::fromArray($request->validated());
        $result = $this->reportService->createReport($technician->id, $dto);

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'REPORT_ALREADY_EXISTS' => 409,
                'UNAUTHORIZED_ACCESS' => 403,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'STORE_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ], 201);
    }

    /**
     * به‌روزرسانی گزارش توسط تکنسین
     * PUT /api/technician/order-reports/{id}
     */
    public function update(StoreTechnicianOrderReportRequest $request, int $id)
    {
        $technician = $request->user();
        
        $dto = TechnicianOrderReportDTO::fromArray($request->validated());
        $result = $this->reportService->updateReport($technician->id, $id, $dto);

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'UNAUTHORIZED_ACCESS' => 403,
                'REPORT_ALREADY_CONFIRMED' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'UPDATE_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }

    /**
     * مشاهده یک گزارش
     * GET /api/technician/order-reports/{id}
     */
    public function show(int $id)
    {
        $result = $this->reportService->getReport($id);

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'REPORT_NOT_FOUND' => 404,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'GET_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

    /**
     * مشاهده گزارش بر اساس شناسه سفارش
     * GET /api/order-reports/by-order/{orderId}
     */
    public function showByOrder(string $orderId)
    {
        $result = $this->reportService->getReportByOrder((int) $orderId);

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'REPORT_NOT_FOUND' => 404,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'GET_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

    /**
     * لیست گزارش‌های تکنسین
     * GET /api/technician/order-reports
     */
    public function index(Request $request)
    {
        $technician = $request->user();
        $confirmedOnly = $request->boolean('confirmed_only', false);

        $result = $this->reportService->getTechnicianReports($technician->id, $confirmedOnly);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'GET_ERROR'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

    /**
     * تایید گزارش توسط کاربر
     * POST /api/order-reports/confirm
     */
    public function confirm(ConfirmTechnicianOrderReportRequest $request)
    {
        $user = $request->user();
        
        $result = $this->reportService->confirmReport($user->id, $request->report_id);

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'UNAUTHORIZED_ACCESS' => 403,
                'ALREADY_CONFIRMED' => 409,
                'REPORT_NOT_FOUND' => 404,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'CONFIRM_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }

    /**
     * ارسال سفارش به لوپ توسط تکنسین
     * POST /api/technician/orders/{orderId}/send-to-loop
     */
    public function sendToLoop(int $orderId, Request $request)
    {
        $technician = $request->user();
        
        $result = $this->reportService->sendOrderToLoop($technician->id, $orderId);

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'UNAUTHORIZED_ACCESS' => 403,
                'REPORT_NOT_CONFIRMED' => 400,
                'ALREADY_SENT_TO_LOOP' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'SEND_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }
    public function doneInPlace(int $orderId, Request $request)
    {
        $technician = $request->user();
        $technician_in_place_description = $request->technician_in_place_description;
        $result = $this->reportService->doneInPlace($technician->id, $orderId, $technician_in_place_description);

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'UNAUTHORIZED_ACCESS' => 403,
                'ALREADY_DONE_IN_PLACE' => 409,
                'DONE_IN_PLACE_ERROR' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'DONE_IN_PLACE_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }

    /**
     * به‌روزرسانی اطلاعات لوپ (duration و loop_description) توسط تکنسین
     * PATCH /api/technician/orders/{orderId}/loop-info
     */
    public function updateLoopInfo(int $orderId, UpdateOrderLoopInfoRequest $request)
    {
        $technician = $request->user();
        
        $dto = UpdateOrderLoopInfoDTO::fromArray($request->validated());
        $result = $this->reportService->updateOrderLoopInfo($technician->id, $orderId, $dto->toArray());

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'UNAUTHORIZED_ACCESS' => 403,
                'NOT_SENT_TO_LOOP' => 400,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'UPDATE_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }

    /**
     * ثبت توضیحات تکنسین و تاریخ/ساعت دلخواه (قبل از شروع سفارش)
     * POST /api/technician/orders/{orderId}/technician-description
     */
    public function setTechnicianDescription(int $orderId, SetTechnicianDescriptionRequest $request)
    {
        $technician = $request->user();
        
        $dto = SetTechnicianDescriptionDTO::fromArray($request->validated());
        $result = $this->reportService->setTechnicianDescription(
            $technician->id,
            $orderId,
            $dto->technician_des,
            $dto->date,
            $dto->time,
            $dto->technician_price
        );

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'UNAUTHORIZED_ACCESS' => 403,
                'ORDER_ALREADY_STARTED' => 400,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'UPDATE_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }

    /**
     * ثبت زمان حرکت تکنسین (راه افتادن)
     * POST /api/technician/orders/{orderId}/set-off
     */
    public function setOff(int $orderId, Request $request)
    {
        $technician = $request->user();
        
        $result = $this->reportService->setOffToAddress($technician->id, $orderId);

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'UNAUTHORIZED_ACCESS' => 403,
                'ORDER_NOT_FOUND' => 404,
                'ALREADY_SET_OFF' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'SET_OFF_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }

    /**
     * ثبت زمان رسیدن تکنسین
     * POST /api/technician/orders/{orderId}/arrive
     */
    public function arrive(int $orderId, Request $request)
    {
        $technician = $request->user();
        
        $result = $this->reportService->arriveAtAddress($technician->id, $orderId);

        if (!$result['success']) {
            $statusCode = match($result['error_code'] ?? '') {
                'UNAUTHORIZED_ACCESS' => 403,
                'ORDER_NOT_FOUND' => 404,
                'NOT_SET_OFF_YET' => 400,
                'ALREADY_ARRIVED' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'ARRIVE_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }
}

