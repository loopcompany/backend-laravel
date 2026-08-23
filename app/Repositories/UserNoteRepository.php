<?php

namespace App\Repositories;

use App\Models\UserNote;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class UserNoteRepository
{
    /**
     * ایجاد یادداشت جدید
     *
     * @param array $data
     * @return UserNote|null
     */
    public function create(array $data): ?UserNote
    {
        try {
            return UserNote::create($data);
        } catch (\Exception $e) {
            Log::error('Error creating user note: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * دریافت تمام یادداشت‌های یک کاربر
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserNotes(int $userId): Collection
    {
        try {
            return UserNote::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error fetching user notes: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * دریافت یک یادداشت خاص متعلق به کاربر
     *
     * @param int $id
     * @param int $userId
     * @return UserNote|null
     */
    public function findByIdAndUser(int $id, int $userId): ?UserNote
    {
        try {
            return UserNote::where('id', $id)
                ->where('user_id', $userId)
                ->first();
        } catch (\Exception $e) {
            Log::error('Error finding user note: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * بروزرسانی یادداشت
     *
     * @param UserNote $note
     * @param array $data
     * @return bool
     */
    public function update(UserNote $note, array $data): bool
    {
        try {
            return $note->update($data);
        } catch (\Exception $e) {
            Log::error('Error updating user note: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * حذف یادداشت
     *
     * @param UserNote $note
     * @return bool
     */
    public function delete(UserNote $note): bool
    {
        try {
            return $note->delete();
        } catch (\Exception $e) {
            Log::error('Error deleting user note: ' . $e->getMessage());
            return false;
        }
    }
}
