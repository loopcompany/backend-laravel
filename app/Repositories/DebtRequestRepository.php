<?php

namespace App\Repositories;

use App\Models\DebtRequest;
use Illuminate\Database\Eloquent\Collection;

class DebtRequestRepository
{
    /**
     * Create a new debt request.
     *
     * @param array $data
     * @return DebtRequest
     */
    public function create(array $data): DebtRequest
    {
        return DebtRequest::create($data);
    }

    /**
     * Get all debt requests for a specific technician.
     *
     * @param int $technicianId
     * @return Collection
     */
    public function getByTechnicianId(int $technicianId): Collection
    {
        return DebtRequest::where('technician_id', $technicianId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find a debt request by ID.
     *
     * @param int $id
     * @return DebtRequest|null
     */
    public function findById(int $id): ?DebtRequest
    {
        return DebtRequest::find($id);
    }

    /**
     * Get all debt requests with technician relationship.
     * (For admin panel)
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return DebtRequest::with(['technician.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Update the status of a debt request.
     *
     * @param int $id
     * @param int $status
     * @return bool
     */
    public function updateStatus(int $id, int $status): bool
    {
        return DebtRequest::where('id', $id)->update(['status' => $status]);
    }
}
