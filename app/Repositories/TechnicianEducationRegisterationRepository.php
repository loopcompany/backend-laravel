<?php

namespace App\Repositories;

use App\Models\TechnicianEducationRegisteration;
use Illuminate\Support\Facades\Log;

class TechnicianEducationRegisterationRepository
{
    /**
     * ثبت درخواست آموزش جدید برای تکنسین
     *
     * @param array $data
     * @return TechnicianEducationRegisteration|null
     */
    public function create(array $data): ?TechnicianEducationRegisteration
    {
        try {
            return TechnicianEducationRegisteration::create($data);
        } catch (\Exception $e) {
            Log::error('Error creating technician education registeration: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * دریافت درخواست‌های آموزش یک تکنسین
     *
     * @param int $technicianId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTechnicianRegisterations(int $technicianId)
    {
        try {
            return TechnicianEducationRegisteration::where('technician_id', $technicianId)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error fetching technician education registerations: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * دریافت یک درخواست آموزش خاص تکنسین
     *
     * @param int $id
     * @param int $technicianId
     * @return TechnicianEducationRegisteration|null
     */
    public function findByIdAndTechnician(int $id, int $technicianId): ?TechnicianEducationRegisteration
    {
        try {
            return TechnicianEducationRegisteration::where('id', $id)
                ->where('technician_id', $technicianId)
                ->first();
        } catch (\Exception $e) {
            Log::error('Error finding technician education registeration: ' . $e->getMessage());
            return null;
        }
    }
}
