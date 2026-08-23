<?php

namespace App\Http\Controllers;

use App\DTOs\ReportViolationDTO;
use App\Http\Requests\StoreReportViolationRequest;
use App\Services\ReportViolationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportViolationController extends Controller
{
    public function __construct(
        private readonly ReportViolationService $service
    ) {}

    /**
     * Submit new report violation
     */
    public function store(StoreReportViolationRequest $request): JsonResponse
    {
        try {
            $dto = ReportViolationDTO::fromArray(
                $request->validated(),
                auth()->id()
            );

            $result = $this->service->submitReport($dto);

            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => $result['report']
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطایی در ثبت گزارش رخ داد.'
            ], 500);
        }
    }

    /**
     * Get user's report violations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);

            $result = $this->service->getUserReports(auth()->id(), $page, $perPage);

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطایی در دریافت گزارش‌ها رخ داد.'
            ], 500);
        }
    }

    /**
     * Get specific report violation
     */
    public function show(int $id): JsonResponse
    {
        try {
            $result = $this->service->getUserReport(auth()->id(), $id);

            return response()->json([
                'status' => 'success',
                'data' => $result['report']
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطایی در دریافت گزارش رخ داد.'
            ], 500);
        }
    }

    /**
     * Update report violation
     */
    public function update(StoreReportViolationRequest $request, int $id): JsonResponse
    {
        try {
            $dto = ReportViolationDTO::fromArray(
                $request->validated(),
                auth()->id()
            );

            $result = $this->service->updateReport(auth()->id(), $id, $dto);

            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => $result['report']
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطایی در بروزرسانی گزارش رخ داد.'
            ], 500);
        }
    }

    /**
     * Delete report violation
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->service->deleteReport(auth()->id(), $id);

            return response()->json([
                'status' => 'success',
                'message' => $result['message']
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطایی در حذف گزارش رخ داد.'
            ], 500);
        }
    }

    /**
     * Search report violations
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $criteria = $request->only(['subject', 'date_from', 'date_to']);
            $perPage = $request->get('per_page', 10);

            $result = $this->service->searchReports(auth()->id(), $criteria, $perPage);

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطایی در جستجو رخ داد.'
            ], 500);
        }
    }
}