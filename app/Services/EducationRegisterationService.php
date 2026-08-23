<?php

namespace App\Services;

use App\Repositories\EducationRegisterationRepository;
use Illuminate\Support\Facades\Log;

class EducationRegisterationService
{
    public function __construct(
        private EducationRegisterationRepository $repository
    ) {}

    /**
     * ثبت درخواست آموزش جدید
     *
     * @param int $userId
     * @param array $data
     * @return array
     */
    public function createRegisteration(int $userId, array $data): array
    {
        try {
            $registerationData = [
                'user_id' => $userId,
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
     * دریافت لیست درخواست‌های آموزش کاربر
     *
     * @param int $userId
     * @return array
     */
    public function getUserRegisterations(int $userId): array
    {
        try {
            $registerations = $this->repository->getUserRegisterations($userId);

            return [
                'success' => true,
                'data' => $registerations
            ];

        } catch (\Exception $e) {
            Log::error('Error in getUserRegisterations service: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در دریافت درخواست‌های آموزش.',
                'error_code' => 'FETCH_REGISTERATIONS_ERROR'
            ];
        }
    }

    /**
     * دریافت جزئیات یک درخواست آموزش
     *
     * @param int $userId
     * @param int $registerationId
     * @return array
     */
    public function getRegisterationDetail(int $userId, int $registerationId): array
    {
        try {
            $registeration = $this->repository->findByIdAndUser($registerationId, $userId);

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
