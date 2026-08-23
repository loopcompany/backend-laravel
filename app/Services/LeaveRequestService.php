<?php

namespace App\Services;

use App\Repositories\LeaveRequestRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveRequestService
{
    protected LeaveRequestRepository $leaveRequestRepository;

    public function __construct(LeaveRequestRepository $leaveRequestRepository)
    {
        $this->leaveRequestRepository = $leaveRequestRepository;
    }

    /**
     * Create a new leave request for a technician.
     *
     * @param array $data
     * @param int $technicianId
     * @return array
     */
    public function createRequest(array $data, int $technicianId): array
    {
        try {
            DB::beginTransaction();

            $data['technician_id'] = $technicianId;
            $data['status'] = 0; // pending by default

            $leaveRequest = $this->leaveRequestRepository->create($data);

            DB::commit();

            Log::info('Leave request created successfully', [
                'leave_request_id' => $leaveRequest->id,
                'technician_id' => $technicianId,
                'type' => $data['type'],
            ]);

            return [
                'success' => true,
                'message' => 'درخواست مرخصی شما با موفقیت ثبت شد',
                'data' => $leaveRequest,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create leave request', [
                'error' => $e->getMessage(),
                'technician_id' => $technicianId,
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت درخواست مرخصی',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all leave requests for a specific technician.
     *
     * @param int $technicianId
     * @return array
     */
    public function getMyRequests(int $technicianId): array
    {
        try {
            $requests = $this->leaveRequestRepository->getByTechnicianId($technicianId);

            $formattedRequests = $requests->map(function ($request) {
                return [
                    'id' => $request->id,
                    'type' => $request->type,
                    'type_label' => $this->getTypeLabel($request->type),
                    'date' => $request->date,
                    'to_date' => $request->to_date,
                    'houre' => $request->houre,
                    'description' => $request->description,
                    'status' => $request->status,
                    'response' => $request->response,
                    'status_label' => $this->getStatusLabel($request->status),
                    'created_at' => $request->created_at->toIso8601String(),
                    'updated_at' => $request->updated_at->toIso8601String(),
                ];
            });

            return [
                'success' => true,
                'data' => $formattedRequests,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get leave requests', [
                'error' => $e->getMessage(),
                'technician_id' => $technicianId,
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست درخواست‌ها',
            ];
        }
    }

    /**
     * Get a specific leave request detail.
     *
     * @param int $requestId
     * @param int $technicianId
     * @return array
     */
    public function getRequestDetail(int $requestId, int $technicianId): array
    {
        try {
            $request = $this->leaveRequestRepository->findById($requestId);

            if (!$request) {
                return [
                    'success' => false,
                    'message' => 'درخواست مورد نظر یافت نشد',
                    'error_code' => 'REQUEST_NOT_FOUND',
                ];
            }

            // Check ownership
            if ($request->technician_id != $technicianId) {
                return [
                    'success' => false,
                    'message' => 'دسترسی غیرمجاز',
                    'error_code' => 'UNAUTHORIZED_ACCESS',
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $request->id,
                    'type' => $request->type,
                    'type_label' => $this->getTypeLabel($request->type),
                    'date' => $request->date,
                    'to_date' => $request->to_date,
                    'houre' => $request->houre,
                    'description' => $request->description,
                    'status' => $request->status,
                    'response' => $request->response,
                    'status_label' => $this->getStatusLabel($request->status),
                    'created_at' => $request->created_at->toIso8601String(),
                    'updated_at' => $request->updated_at->toIso8601String(),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get leave request detail', [
                'error' => $e->getMessage(),
                'request_id' => $requestId,
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت جزئیات درخواست',
            ];
        }
    }

    /**
     * Get Persian label for leave type.
     *
     * @param string $type
     * @return string
     */
    private function getTypeLabel(string $type): string
    {
        return match ($type) {
            'hourly' => 'ساعتی',
            'daily' => 'روزانه',
            default => 'نامشخص',
        };
    }

    /**
     * Get Persian label for status.
     *
     * @param int $status
     * @return string
     */
    private function getStatusLabel(int $status): string
    {
        return match ($status) {
            0 => 'در انتظار بررسی',
            1 => 'تأیید شده',
            2 => 'رد شده',
            default => 'نامشخص',
        };
    }
}
