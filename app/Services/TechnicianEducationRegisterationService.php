<?php

namespace App\Services;

use App\Repositories\TechnicianEducationRegisterationRepository;
use Illuminate\Support\Facades\Log;

class TechnicianEducationRegisterationService
{
    public function __construct(
        private TechnicianEducationRegisterationRepository $repository
    ) {}

    /**
     * ثبت درخواست آموزش جدید برای تکنسین
     *
     * @param int $technicianId
     * @param array $data
     * @return array
     */
    public function createRegisteration(int $technicianId, array $data): array
    {
        try {
            $registerationData = [
                'technician_id' => $technicianId,
                'telephone' => $data['telephone'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'description' => $data['description'] ?? null,
            ];

            $registeration = $this->repository->create($registerationData);

            if (!$registeration) {
                return [
                    'success' => false,
                    'message' => 'خطا در ثبت درخواست آموزش.',
                    'error_code' => 'REGISTERATION_CREATE_ERROR'
                ];
            }

            return [
                'success' => true,
                'message' => 'درخواست آموزش با موفقیت ثبت شد.',
                'data' => $registeration
            ];

        } catch (\Exception $e) {
            Log::error('Error in createRegisteration service: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در ثبت درخواست آموزش.',
                'error_code' => 'REGISTERATION_SERVICE_ERROR'
            ];
        }
    }

    /**
     * دریافت لیست درخواست‌های آموزش تکنسین
     *
     * @param int $technicianId
     * @return array
     */
    public function getTechnicianRegisterations(int $technicianId): array
    {
        try {
            $registerations = $this->repository->getTechnicianRegisterations($technicianId);

            return [
                'success' => true,
                'data' => $registerations
            ];

        } catch (\Exception $e) {
            Log::error('Error in getTechnicianRegisterations service: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در دریافت درخواست‌های آموزش.',
                'error_code' => 'FETCH_REGISTERATIONS_ERROR'
            ];
        }
    }

    /**
     * دریافت جزئیات یک درخواست آموزش تکنسین
     *
     * @param int $technicianId
     * @param int $registerationId
     * @return array
     */
    public function getRegisterationDetail(int $technicianId, int $registerationId): array
    {
        try {
            $registeration = $this->repository->findByIdAndTechnician($registerationId, $technicianId);

            if (!$registeration) {
                return [
                    'success' => false,
                    'message' => 'درخواست آموزش یافت نشد یا به شما تعلق ندارد.',
                    'error_code' => 'REGISTERATION_NOT_FOUND'
                ];
            }

            return [
                'success' => true,
                'data' => $registeration
            ];

        } catch (\Exception $e) {
            Log::error('Error in getRegisterationDetail service: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در دریافت جزئیات درخواست آموزش.',
                'error_code' => 'FETCH_REGISTERATION_DETAIL_ERROR'
            ];
        }
    }
}
