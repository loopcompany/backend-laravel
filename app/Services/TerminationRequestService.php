<?php

namespace App\Services;

use App\DTOs\TerminationRequestDTO;
use App\Repositories\TerminationRequestRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TerminationRequestService
{
    public function __construct(
        private TerminationRequestRepository $repository
    ) {}

    /**
     * Create a new termination request
     */
    public function create(TerminationRequestDTO $dto): array
    {
        try {
            DB::beginTransaction();

            $terminationRequest = $this->repository->create($dto->toArray());

            DB::commit();

            return [
                'success' => true,
                'message' => 'درخواست قطع همکاری با موفقیت ثبت شد.',
                'data' => $terminationRequest->load('technician:id,name,phone'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating termination request: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'خطا در ثبت درخواست قطع همکاری.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all termination requests for a technician
     */
    public function getListByTechnician(int $technicianId): array
    {
        try {
            $requests = $this->repository->getByTechnician($technicianId);

            return [
                'success' => true,
                'message' => 'لیست درخواست‌های قطع همکاری با موفقیت دریافت شد.',
                'data' => $requests,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching termination requests: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست درخواست‌های قطع همکاری.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get details of a specific termination request
     */
    public function getDetail(int $id, int $technicianId): array
    {
        try {
            $request = $this->repository->findByIdAndTechnician($id, $technicianId);

            if (!$request) {
                return [
                    'success' => false,
                    'message' => 'درخواست قطع همکاری یافت نشد.',
                ];
            }

            return [
                'success' => true,
                'message' => 'جزئیات درخواست قطع همکاری با موفقیت دریافت شد.',
                'data' => $request,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching termination request detail: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'خطا در دریافت جزئیات درخواست قطع همکاری.',
                'error' => $e->getMessage(),
            ];
        }
    }
}
