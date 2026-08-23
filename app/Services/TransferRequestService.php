<?php

namespace App\Services;

use App\DTOs\TransferRequestDTO;
use App\Repositories\TransferRequestRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferRequestService
{
    public function __construct(
        private TransferRequestRepository $repository
    ) {}

    /**
     * Create a new transfer request
     */
    public function create(TransferRequestDTO $dto): array
    {
        try {
            DB::beginTransaction();

            $transferRequest = $this->repository->create($dto->toArray());

            DB::commit();

            return [
                'success' => true,
                'message' => 'درخواست انتقال/سمت با موفقیت ثبت شد.',
                'data' => $transferRequest->load('technician:id,name,phone'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating transfer request: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'خطا در ثبت درخواست انتقال/سمت.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all transfer requests for a technician
     */
    public function getListByTechnician(int $technicianId): array
    {
        try {
            $requests = $this->repository->getByTechnician($technicianId);

            return [
                'success' => true,
                'message' => 'لیست درخواست‌های انتقال/سمت با موفقیت دریافت شد.',
                'data' => $requests,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching transfer requests: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست درخواست‌های انتقال/سمت.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get details of a specific transfer request
     */
    public function getDetail(int $id, int $technicianId): array
    {
        try {
            $request = $this->repository->findByIdAndTechnician($id, $technicianId);

            if (!$request) {
                return [
                    'success' => false,
                    'message' => 'درخواست انتقال/سمت یافت نشد.',
                ];
            }

            return [
                'success' => true,
                'message' => 'جزئیات درخواست انتقال/سمت با موفقیت دریافت شد.',
                'data' => $request,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching transfer request detail: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'خطا در دریافت جزئیات درخواست انتقال/سمت.',
                'error' => $e->getMessage(),
            ];
        }
    }
}
