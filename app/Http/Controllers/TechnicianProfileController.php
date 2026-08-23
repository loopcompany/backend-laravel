<?php

namespace App\Http\Controllers;

use App\DTOs\UpdateTechnicianPersonalInfoDTO;
use App\DTOs\UpdateTechnicianVehicleInfoDTO;
use App\DTOs\UpdateTechnicianBankInfoDTO;
use App\DTOs\UpdateTechnicianPasswordDTO;
use App\Http\Requests\SetEmergencyHelpRequest;
use App\Http\Requests\SetTechnicianOpinionRequest;
use App\Http\Requests\TechnicianCancelOrderRequest;
use App\Http\Requests\UpdateTechnicianPersonalInfoRequest;
use App\Http\Requests\UpdateTechnicianVehicleInfoRequest;
use App\Http\Requests\UpdateTechnicianBankInfoRequest;
use App\Http\Requests\UpdateTechnicianPasswordRequest;
use App\Services\OrderService;
use App\Services\TechnicianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TechnicianProfileController extends Controller
{
    public function __construct(
        protected TechnicianService $technicianService,
        protected OrderService $orderService
    ) {}

    /**
     * به‌روزرسانی اطلاعات شخصی تکنسین (بخش اول)
     */
    public function updatePersonalInfo(UpdateTechnicianPersonalInfoRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $dto = UpdateTechnicianPersonalInfoDTO::fromArray($request->validated());
        $profilePhoto = $request->file('profile_photo');

        $result = $this->technicianService->updatePersonalInfo($user, $dto, $profilePhoto);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * به‌روزرسانی اطلاعات وسیله نقلیه تکنسین (بخش دوم)
     */
    public function updateVehicleInfo(UpdateTechnicianVehicleInfoRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $dto = UpdateTechnicianVehicleInfoDTO::fromArray($request->validated());

        $result = $this->technicianService->updateVehicleInfo($user, $dto);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * به‌روزرسانی اطلاعات بانکی تکنسین (بخش سوم)
     */
    public function updateBankInfo(UpdateTechnicianBankInfoRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $dto = UpdateTechnicianBankInfoDTO::fromArray($request->validated());

        $result = $this->technicianService->updateBankInfo($user, $dto);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * تغییر رمز عبور تکنسین
     */
    public function updatePassword(UpdateTechnicianPasswordRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $dto = UpdateTechnicianPasswordDTO::fromArray($request->validated());

        $result = $this->technicianService->updatePassword($user, $dto);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
    public function updateAtWork(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }


        $result = $this->technicianService->updateAtWork($user);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * دریافت لیست سفارشات تکنسین
     */
    public function getMyOrders(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $status = $request->query('status'); // pending, in_progress, completed, cancelled
        $perPage = $request->query('per_page', 15);

        $result = $this->technicianService->getMyOrders($user, $status, (int)$perPage);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * دریافت جزئیات یک سفارش برای تکنسین
     */
    public function getOrderDetail(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->orderService->getTechnicianOrderDetail($user->id, $orderId);

        if (!$result['success']) {
            $statusCode = ($result['error_code'] ?? '') == 'ORDER_NOT_FOUND' ? 404 : 400;
            
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'FETCH_ORDER_DETAIL_ERROR'
            ], $statusCode);
        }

        return response()->json($result['data'], 200);
    }

    /**
     * شروع کار سفارش توسط تکنسین
     */
    public function startOrder(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->orderService->startOrderByTechnician($user->id, $orderId);

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'TECHNICIAN_NOT_ARRIVED' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'START_ORDER_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message']
        ], 200);
    }

    /**
     * پایان کار سفارش توسط تکنسین
     */
    public function endOrder(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->orderService->endOrderByTechnician($user->id, $orderId);

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'ORDER_NOT_STARTED' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'END_ORDER_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message']
        ], 200);
    }

    /**
     * لغو سفارش توسط تکنسین
     */
    public function cancelOrder(TechnicianCancelOrderRequest $request, int $orderId): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->orderService->cancelOrderByTechnician(
            $user->id, 
            $orderId,
            $request->validated('technician_cancel_reason')
        );

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'ORDER_ALREADY_CANCELLED' => 409,
                'ORDER_ALREADY_STARTED' => 409,
                'ORDER_COMPLETED' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'CANCEL_ORDER_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message']
        ], 200);
    }

    /**
     * ثبت درخواست کمک اضطراری توسط تکنسین
     */
    public function setEmergencyHelp(SetEmergencyHelpRequest $request, int $orderId): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->orderService->setEmergencyHelp(
            $orderId,
            $user->id,
            $request->validated('emergency_help')
        );

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'INVALID_ORDER_STATUS' => 409,
                'ORDER_NOT_STARTED' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'EMERGENCY_HELP_ERROR'
            ], $statusCode);
        }

        return response()->json($result, 200);
    }

    /**
     * ثبت نظر تکنسین در پایان سفارش
     */
    public function setTechnicianOpinion(SetTechnicianOpinionRequest $request, int $orderId): JsonResponse
    {
        $user = $request->user('sanctum');

        // بررسی اینکه کاربر یک تکنسین است
        if (!($user instanceof \App\Models\Technician)) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->orderService->setTechnicianOpinion(
            $orderId,
            $user->id,
            $request->validated('technician_opinion')
        );

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'ORDER_NOT_FINISHED' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'TECHNICIAN_OPINION_ERROR'
            ], $statusCode);
        }

        return response()->json($result, 200);
    }
}
