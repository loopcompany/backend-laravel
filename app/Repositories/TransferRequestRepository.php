<?php

namespace App\Repositories;

use App\Models\TransferRequest;
use Illuminate\Database\Eloquent\Collection;

class TransferRequestRepository
{
    /**
     * Create a new transfer request
     */
    public function create(array $data): TransferRequest
    {
        return TransferRequest::create($data);
    }

    /**
     * Get all transfer requests for a specific technician
     */
    public function getByTechnician(int $technicianId): Collection
    {
        return TransferRequest::where('technician_id', $technicianId)
            ->with('technician:id,name,phone')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get a specific transfer request by ID
     */
    public function findById(int $id): ?TransferRequest
    {
        return TransferRequest::with('technician:id,name,phone')->find($id);
    }

    /**
     * Get a specific transfer request by ID and technician ID
     */
    public function findByIdAndTechnician(int $id, int $technicianId): ?TransferRequest
    {
        return TransferRequest::where('id', $id)
            ->where('technician_id', $technicianId)
            ->with('technician:id,name,phone')
            ->first();
    }

    /**
     * Update transfer request status
     */
    public function updateStatus(int $id, int $status): bool
    {
        return TransferRequest::where('id', $id)->update(['status' => $status]);
    }
}
