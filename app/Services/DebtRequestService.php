<?php

namespace App\Services;

use App\Repositories\DebtRequestRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DebtRequestService
{
    protected DebtRequestRepository $debtRequestRepository;

    public function __construct(DebtRequestRepository $debtRequestRepository)
    {
        $this->debtRequestRepository = $debtRequestRepository;
    }

    /**
     * Create a new debt request for a technician.
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

            // اگر نوع sponsor باشد، فیلدهای مربوط به free را null می‌کنیم
            if ($data['type'] === 'sponsor') {
                $data['amount'] = null;
                $data['sponsor'] = null;
                $data['month'] = null;
                $data['urgent_description'] = null;
            }

            $debtRequest = $this->debtRequestRepository->create($data);

            DB::commit();

            Log::info('Debt request created successfully', [
                'debt_request_id' => $debtRequest->id,
                'technician_id' => $technicianId,
                'type' => $data['type'],
            ]);

            return [
                'success' => true,
                'message' => 'درخواست وام شما با موفقیت ثبت شد',
                'data' => $debtRequest,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create debt request', [
                'error' => $e->getMessage(),
                'technician_id' => $technicianId,
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت درخواست وام',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all debt requests for a specific technician.
     *
     * @param int $technicianId
     * @return array
     */
    public function getMyRequests(int $technicianId): array
    {
        try {
            $requests = $this->debtRequestRepository->getByTechnicianId($technicianId);

            $formattedRequests = $requests->map(function ($request) {
                return [
                    'id' => $request->id,
                    'type' => $request->type,
                    'type_label' => $this->getTypeLabel($request->type),
                    'amount' => $request->amount,
                    'description' => $request->description,
                    'sponsor' => $request->sponsor,
                    'month' => $request->month,
                    'urgent_description' => $request->urgent_description,
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
            Log::error('Failed to get debt requests', [
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
     * Get a specific debt request detail.
     *
     * @param int $requestId
     * @param int $technicianId
     * @return array
     */
    public function getRequestDetail(int $requestId, int $technicianId): array
    {
        try {
            $request = $this->debtRequestRepository->findById($requestId);

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
                    'amount' => $request->amount,
                    'description' => $request->description,
                    'sponsor' => $request->sponsor,
                    'month' => $request->month,
                    'urgent_description' => $request->urgent_description,
                    'status' => $request->status,
                    'response' => $request->response,
                    'status_label' => $this->getStatusLabel($request->status),
                    'created_at' => $request->created_at->toIso8601String(),
                    'updated_at' => $request->updated_at->toIso8601String(),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get debt request detail', [
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
     * Get Persian label for debt type.
     *
     * @param string $type
     * @return string
     */
    private function getTypeLabel(string $type): string
    {
        return match ($type) {
            'sponsor' => 'ضامن/ضمانت‌نامه',
            'free' => 'وام بدون بهره',
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
