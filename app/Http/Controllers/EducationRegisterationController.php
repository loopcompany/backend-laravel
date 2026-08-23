<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEducationRegisterationRequest;
use App\Services\EducationRegisterationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EducationRegisterationController extends Controller
{
    public function __construct(
        private EducationRegisterationService $service
    ) {}

    /**
     * ثبت درخواست آموزش جدید
     * 
     * @param StoreEducationRegisterationRequest $request
     * @return JsonResponse
     */
    public function store(StoreEducationRegisterationRequest $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->service->createRegisteration($user->id, $request->validated());

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * دریافت لیست درخواست‌های آموزش کاربر
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

        $result = $this->service->getUserRegisterations($user->id);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * دریافت جزئیات یک درخواست آموزش
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

        $result = $this->service->getRegisterationDetail($user->id, $id);

        $statusCode = ($result['error_code'] ?? '') == 'REGISTERATION_NOT_FOUND' ? 404 : ($result['success'] ? 200 : 400);

        return response()->json($result, $statusCode);
    }
}
