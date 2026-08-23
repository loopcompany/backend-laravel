<?php

namespace App\Services;

use App\DTOs\ManpowerRequestDTO;
use App\Repositories\ManpowerRequestRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManpowerRequestService
{
    public function __construct(
        private ManpowerRequestRepository $repository
    ) {}

    /**
     * Create a new manpower request
     */
    public function create(ManpowerRequestDTO $dto): array
    {
        try {
            DB::beginTransaction();

            $manpowerRequest = $this->repository->create($dto->toArray());

            DB::commit();

            return [
                'success' => true,
                'message' => 'درخواست نیروی انسانی با موفقیت ثبت شد.',
                'data' => $manpowerRequest->load('technician:id,name,phone'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating manpower request: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'خطا در ثبت درخواست نیروی انسانی.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all manpower requests for a technician
     */
    public function getListByTechnician(int $technicianId): array
    {
        try {
            $requests = $this->repository->getByTechnician($technicianId);

            return [
                'success' => true,
                'message' => 'لیست درخواست‌های نیروی انسانی با موفقیت دریافت شد.',
                'data' => $requests,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching manpower requests: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست درخواست‌های نیروی انسانی.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get details of a specific manpower request
     */
    public function getDetail(int $id, int $technicianId): array
    {
        try {
            $request = $this->repository->findByIdAndTechnician($id, $technicianId);

            if (!$request) {
                return [
                    'success' => false,
                    'message' => 'درخواست نیروی انسانی یافت نشد.',
                ];
            }

            return [
                'success' => true,
                'message' => 'جزئیات درخواست نیروی انسانی با موفقیت دریافت شد.',
                'data' => $request,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching manpower request detail: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'خطا در دریافت جزئیات درخواست نیروی انسانی.',
                'error' => $e->getMessage(),
            ];
        }
    }
}
