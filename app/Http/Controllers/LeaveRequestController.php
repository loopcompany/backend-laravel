<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequestRequest;
use App\Models\Technician;
use App\Services\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    protected LeaveRequestService $leaveRequestService;

    public function __construct(LeaveRequestService $leaveRequestService)
    {
        $this->leaveRequestService = $leaveRequestService;
    }

    /**
     * Store a new leave request.
     *
     * @param StoreLeaveRequestRequest $request
     * @return JsonResponse
     */
    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $technician = auth('sanctum')->user();

        if (!$technician instanceof Technician) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->leaveRequestService->createRequest(
            $request->validated(),
            $technician->id
        );

        if ($result['success']) {
            return response()->json($result, 201);
        }

        return response()->json($result, 500);
    }

    /**
     * Get all leave requests for the authenticated technician.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $technician = auth('sanctum')->user();

        if (!$technician instanceof Technician) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->leaveRequestService->getMyRequests($technician->id);

        return response()->json($result);
    }

    /**
     * Get a specific leave request detail.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $technician = auth('sanctum')->user();

        if (!$technician instanceof Technician) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->leaveRequestService->getRequestDetail($id, $technician->id);

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? null) {
                'REQUEST_NOT_FOUND' => 404,
                'UNAUTHORIZED_ACCESS' => 403,
                default => 500,
            };

            return response()->json($result, $statusCode);
        }

        return response()->json($result);
    }
}
