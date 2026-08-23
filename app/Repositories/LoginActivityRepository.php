<?php

namespace App\Repositories;

use App\Models\LoginActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class LoginActivityRepository
{
    /**
     * Create a new login activity record
     * 
     * @param array $data
     * @return LoginActivity|null
     */
    public function create(array $data): ?LoginActivity
    {
        try {
            return LoginActivity::create($data);
        } catch (\Exception $e) {
            // Log error but don't throw - logging should never break authentication
            \Log::error('Failed to create login activity', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get activities by user type and user ID
     * 
     * @param string $userType
     * @param int $userId
     * @param array $filters
     * @return Collection
     */
    public function getByUser(string $userType, int $userId, array $filters = []): Collection
    {
        $query = LoginActivity::where('user_type', $userType)
            ->where('user_id', $userId);

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get all activities with filters (for admin)
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = LoginActivity::query();

        if (isset($filters['user_type'])) {
            $query->where('user_type', $filters['user_type']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Delete old activities (for cleanup/privacy)
     * 
     * @param int $daysToKeep
     * @return int Number of deleted records
     */
    public function deleteOldActivities(int $daysToKeep = 90): int
    {
        try {
            $date = now()->subDays($daysToKeep);
            return LoginActivity::where('created_at', '<', $date)->delete();
        } catch (\Exception $e) {
            \Log::error('Failed to delete old login activities', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get recent activities count by user
     * 
     * @param string $userType
     * @param int $userId
     * @param int $hours
     * @return int
     */
    public function getRecentActivityCount(string $userType, int $userId, int $hours = 24): int
    {
        return LoginActivity::where('user_type', $userType)
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();
    }
}
