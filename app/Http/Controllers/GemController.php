<?php

namespace App\Http\Controllers;

use App\Services\GemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GemController extends Controller
{
    public function __construct(
        private GemService $gemService
    ) {}

    /**
     * دریافت لیست gem action های فعال برای گردونه شانس
     * GET /api/gems/actions
     */
    public function getActions(): JsonResponse
    {
        $result = $this->gemService->getActiveGemActions();

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * شرکت در گردونه شانس
     * POST /api/gems/spin
     */
    public function spinWheel(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->gemService->spinLuckyWheel($user->id);

        $statusCode = match ($result['error_code'] ?? null) {
            'ALREADY_PLAYED_THIS_WEEK' => 429, // Too Many Requests
            'NO_ACTIVE_ACTIONS' => 503, // Service Unavailable
            default => $result['success'] ? 201 : 400
        };

        return response()->json($result, $statusCode);
    }

    /**
     * دریافت تاریخچه gem های کاربر
     * GET /api/gems/history
     */
    public function getHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $perPage = $request->input('per_page', 20);
        $result = $this->gemService->getUserGemHistory($user->id, $perPage);

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * بررسی امکان شرکت در گردونه
     * GET /api/gems/can-play
     */
    public function canPlay(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->gemService->canUserPlayWheel($user->id);

        return response()->json($result, 200);
    }
}
