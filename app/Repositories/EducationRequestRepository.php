<?php

namespace App\Repositories;

use App\Models\EducationRequest;
use Illuminate\Database\Eloquent\Collection;

class EducationRequestRepository
{
    /**
     * ایجاد درخواست جدید
     */
    public function create(array $data): EducationRequest
    {
        return EducationRequest::create($data);
    }

    /**
     * دریافت تمام درخواست‌های یک تکنسین
     */
    public function getByTechnicianId(int $technicianId): Collection
    {
        return EducationRequest::where('technician_id', $technicianId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * دریافت یک درخواست با شناسه
     */
    public function findById(int $id): ?EducationRequest
    {
        return EducationRequest::find($id);
    }

    /**
     * دریافت تمام درخواست‌ها (برای ادمین)
     */
    public function getAll(): Collection
    {
        return EducationRequest::with('technician.user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * به‌روزرسانی وضعیت درخواست
     */
    public function updateStatus(int $id, int $status): bool
    {
        $request = EducationRequest::find($id);
        if (!$request) {
            return false;
        }

        return $request->update(['status' => $status]);
    }
}
