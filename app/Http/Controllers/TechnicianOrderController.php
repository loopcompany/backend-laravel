<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnicianEndOrderRequest;
use App\Services\TechnicianOrderService;
use Illuminate\Http\JsonResponse;

class TechnicianOrderController extends Controller
{
    public function __construct(private TechnicianOrderService $service) {}

    public function endOrder(TechnicianEndOrderRequest $request): JsonResponse
    {
        $technician = $request->user();

        if (!$technician) {
            return response()->json([
                'success' => false,
                'message' => 'تکنسین احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->service->endOrder($technician->id, $request->input('orderId'));

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'FORBIDDEN' => 403,
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
}
