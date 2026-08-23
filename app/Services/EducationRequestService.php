<?php

namespace App\Services;

use App\Repositories\EducationRequestRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EducationRequestService
{
    public function __construct(
        private EducationRequestRepository $repository
    ) {}

    /**
     * ثبت درخواست آموزش/مراجعه جدید
     */
    public function createRequest(int $technicianId, string $section, string $description): array
    {
        try {
            DB::beginTransaction();

            $request = $this->repository->create([
                'technician_id' => $technicianId,
                'section' => $section,
                'description' => $description,
                'status' => 0, // pending
            ]);

            DB::commit();

            Log::info('Education request created successfully', [
                'request_id' => $request->id,
                'technician_id' => $technicianId,
                'section' => $section,
            ]);

            return [
                'success' => true,
                'message' => 'درخواست شما با موفقیت ثبت شد',
                'data' => $request
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create education request: ' . $e->getMessage(), [
                'technician_id' => $technicianId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت درخواست',
                'error_code' => 'CREATE_REQUEST_ERROR'
            ];
        }
    }

    /**
     * دریافت لیست درخواست‌های تکنسین
     */
    public function getMyRequests(int $technicianId): array
    {
        try {
            $requests = $this->repository->getByTechnicianId($technicianId);

            return [
                'success' => true,
                'data' => $requests->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'section' => $request->section,
                        'description' => $request->description,
                        'status' => $request->status,
                        'response' => $request->response,
                        'status_label' => $this->getStatusLabel($request->status),
                        'created_at' => $request->created_at->toIso8601String(),
                        'updated_at' => $request->updated_at->toIso8601String(),
                    ];
                })
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get education requests: ' . $e->getMessage(), [
                'technician_id' => $technicianId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست درخواست‌ها',
                'error_code' => 'GET_REQUESTS_ERROR'
            ];
        }
    }

    /**
     * دریافت جزئیات یک درخواست
     */
    public function getRequestDetail(int $requestId, int $technicianId): array
    {
        try {
            $request = $this->repository->findById($requestId);

            if (!$request) {
                return [
                    'success' => false,
                    'message' => 'درخواست مورد نظر یافت نشد',
                    'error_code' => 'REQUEST_NOT_FOUND'
                ];
            }

            // بررسی دسترسی
            if ($request->technician_id != $technicianId) {
                return [
                    'success' => false,
                    'message' => 'دسترسی غیرمجاز',
                    'error_code' => 'UNAUTHORIZED_ACCESS'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $request->id,
                    'section' => $request->section,
                    'description' => $request->description,
                    'status' => $request->status,
                    'response' => $request->response,
                    'status_label' => $this->getStatusLabel($request->status),
                    'created_at' => $request->created_at->toIso8601String(),
                    'updated_at' => $request->updated_at->toIso8601String(),
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get education request detail: ' . $e->getMessage(), [
                'request_id' => $requestId,
                'technician_id' => $technicianId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت جزئیات درخواست',
                'error_code' => 'GET_REQUEST_DETAIL_ERROR'
            ];
        }
    }

    /**
     * دریافت برچسب وضعیت
     */
    private function getStatusLabel(int $status): string
    {
        return match($status) {
            0 => 'در انتظار بررسی',
            1 => 'تأیید شده',
            2 => 'رد شده',
            default => 'نامشخص'
        };
    }
}
