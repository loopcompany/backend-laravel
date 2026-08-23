<?php

namespace App\Repositories;

use App\Models\ManpowerRequest;
use Illuminate\Database\Eloquent\Collection;

class ManpowerRequestRepository
{
    /**
     * Create a new manpower request
     */
    public function create(array $data): ManpowerRequest
    {
        return ManpowerRequest::create($data);
    }

    /**
     * Get all manpower requests for a specific technician
     */
    public function getByTechnician(int $technicianId): Collection
    {
        return ManpowerRequest::where('technician_id', $technicianId)
            ->with('technician:id,name,phone')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get a specific manpower request by ID
     */
    public function findById(int $id): ?ManpowerRequest
    {
        return ManpowerRequest::with('technician:id,name,phone')->find($id);
    }

    /**
     * Get a specific manpower request by ID and technician ID
     */
    public function findByIdAndTechnician(int $id, int $technicianId): ?ManpowerRequest
    {
        return ManpowerRequest::where('id', $id)
            ->where('technician_id', $technicianId)
            ->with('technician:id,name,phone')
            ->first();
    }

    /**
     * Update manpower request status
     */
    public function updateStatus(int $id, int $status): bool
    {
        return ManpowerRequest::where('id', $id)->update(['status' => $status]);
    }
}
