<?php

namespace App\Repositories;

use App\Models\TerminationRequest;
use Illuminate\Database\Eloquent\Collection;

class TerminationRequestRepository
{
    /**
     * Create a new termination request
     */
    public function create(array $data): TerminationRequest
    {
        return TerminationRequest::create($data);
    }

    /**
     * Get all termination requests for a specific technician
     */
    public function getByTechnician(int $technicianId): Collection
    {
        return TerminationRequest::where('technician_id', $technicianId)
            ->with('technician:id,name,phone')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get a specific termination request by ID
     */
    public function findById(int $id): ?TerminationRequest
    {
        return TerminationRequest::with('technician:id,name,phone')->find($id);
    }

    /**
     * Get a specific termination request by ID and technician ID
     */
    public function findByIdAndTechnician(int $id, int $technicianId): ?TerminationRequest
    {
        return TerminationRequest::where('id', $id)
            ->where('technician_id', $technicianId)
            ->with('technician:id,name,phone')
            ->first();
    }

    /**
     * Update termination request status
     */
    public function updateStatus(int $id, int $status): bool
    {
        return TerminationRequest::where('id', $id)->update(['status' => $status]);
    }
}
