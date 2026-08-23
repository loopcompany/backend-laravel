<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTechnicianEducationRegisterationRequest;
use App\Models\Technician;
use App\Services\TechnicianEducationRegisterationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TechnicianEducationRegisterationController extends Controller
{
    public function __construct(
        private TechnicianEducationRegisterationService $service
    ) {}

    /**
     * ثبت درخواست آموزش جدید
     *
     * @param StoreTechnicianEducationRegisterationRequest $request
     * @return JsonResponse
     */
    public function store(StoreTechnicianEducationRegisterationRequest $request): JsonResponse
    {
        $technician = auth('sanctum')->user();

        if (!$technician instanceof Technician) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است یا دسترسی ندارد.'
            ], 401);
        }

        $result = $this->service->createRegisteration(
            $technician->id,
            $request->validated()
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data']
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'error_code' => $result['error_code']
        ], 400);
    }

    /**
     * دریافت لیست درخواست‌های آموزش تکنسین
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
                'message' => 'کاربر احراز هویت نشده است یا دسترسی ندارد.'
            ], 401);
        }

        $result = $this->service->getTechnicianRegisterations($technician->id);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'total' => $result['data']->count()
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'error_code' => $result['error_code']
        ], 400);
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
        $technician = auth('sanctum')->user();

        if (!$technician instanceof Technician) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است یا دسترسی ندارد.'
            ], 401);
        }

        $result = $this->service->getRegisterationDetail($technician->id, $id);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => $result['data']
            ], 200);
        }

        // اگر درخواست یافت نشد، 404 برمی‌گردانیم
        if ($result['error_code'] === 'REGISTERATION_NOT_FOUND') {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 404);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'error_code' => $result['error_code']
        ], 400);
    }
}
