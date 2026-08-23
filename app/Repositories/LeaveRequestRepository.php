<?php

namespace App\Repositories;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Collection;

class LeaveRequestRepository
{
    /**
     * Create a new leave request.
     *
     * @param array $data
     * @return LeaveRequest
     */
    public function create(array $data): LeaveRequest
    {
        return LeaveRequest::create($data);
    }

    /**
     * Get all leave requests for a specific technician.
     *
     * @param int $technicianId
     * @return Collection
     */
    public function getByTechnicianId(int $technicianId): Collection
    {
        return LeaveRequest::where('technician_id', $technicianId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find a leave request by ID.
     *
     * @param int $id
     * @return LeaveRequest|null
     */
    public function findById(int $id): ?LeaveRequest
    {
        return LeaveRequest::find($id);
    }

    /**
     * Get all leave requests with technician relationship.
     * (For admin panel)
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return LeaveRequest::with(['technician.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Update the status of a leave request.
     *
     * @param int $id
     * @param int $status
     * @return bool
     */
    public function updateStatus(int $id, int $status): bool
    {
        return LeaveRequest::where('id', $id)->update(['status' => $status]);
    }
}
