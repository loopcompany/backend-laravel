<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDebtRequestRequest;
use App\Models\Technician;
use App\Services\DebtRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebtRequestController extends Controller
{
    protected DebtRequestService $debtRequestService;

    public function __construct(DebtRequestService $debtRequestService)
    {
        $this->debtRequestService = $debtRequestService;
    }

    /**
     * Store a new debt request.
     *
     * @param StoreDebtRequestRequest $request
     * @return JsonResponse
     */
    public function store(StoreDebtRequestRequest $request): JsonResponse
    {
        $technician = auth('sanctum')->user();

        if (!$technician instanceof Technician) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 403);
        }

        $result = $this->debtRequestService->createRequest(
            $request->validated(),
            $technician->id
        );

        if ($result['success']) {
            return response()->json($result, 201);
        }

        return response()->json($result, 500);
    }

    /**
     * Get all debt requests for the authenticated technician.
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

        $result = $this->debtRequestService->getMyRequests($technician->id);

        return response()->json($result);
    }

    /**
     * Get a specific debt request detail.
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

        $result = $this->debtRequestService->getRequestDetail($id, $technician->id);

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
