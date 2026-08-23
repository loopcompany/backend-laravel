<?php

namespace App\Repositories;

use App\Models\EducationRegisteration;
use Illuminate\Support\Facades\Log;

class EducationRegisterationRepository
{
    /**
     * ثبت درخواست آموزش جدید
     *
     * @param array $data
     * @return EducationRegisteration|null
     */
    public function create(array $data): ?EducationRegisteration
    {
        try {
            return EducationRegisteration::create($data);
        } catch (\Exception $e) {
            Log::error('Error creating education registeration: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * دریافت درخواست‌های آموزش یک کاربر
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserRegisterations(int $userId)
    {
        try {
            return EducationRegisteration::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error fetching user education registerations: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * دریافت یک درخواست آموزش خاص
     *
     * @param int $id
     * @param int $userId
     * @return EducationRegisteration|null
     */
    public function findByIdAndUser(int $id, int $userId): ?EducationRegisteration
    {
        try {
            return EducationRegisteration::where('id', $id)
                ->where('user_id', $userId)
                ->first();
        } catch (\Exception $e) {
            Log::error('Error finding education registeration: ' . $e->getMessage());
            return null;
        }
    }
}
