<?php

namespace App\Services;

use App\Models\Technician;
use App\Models\User;
use App\Repositories\LoginActivityRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoginActivityService
{
    public function __construct(
        protected LoginActivityRepository $repository
    ) {
    }

    /**
     * Log a login activity
     * This method MUST NOT throw exceptions to avoid disrupting authentication
     * 
     * @param string $userType 'user', 'technician', or 'organization'
     * @param int $userId
     * @param Request $request
     * @return bool Success status
     */
    public function logLogin(string $userType, int $userId, Request $request): bool
    {
        try {
            $data = [
                'user_type' => $userType,
                'user_id' => $userId,
                'action' => 'login',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_info' => $this->extractDeviceInfo($request),
                'is_online' => 1,
            ];
            if ($userType == 'user' || $userType=='organization') {
                $user = User::find($userId);
                $user->is_online = 1;
                $user->save();
            } else {
                $tech = Technician::find($userId);
                $tech->is_online = 1;
                $tech->save();
            }
            $result = $this->repository->create($data);

            if ($result) {
                Log::info('Login activity logged', [
                    'user_type' => $userType,
                    'user_id' => $userId,
                    'ip' => $request->ip()
                ]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            // CRITICAL: Never throw - just log the error
            Log::error('Failed to log login activity', [
                'user_type' => $userType,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Log a logout activity
     * This method MUST NOT throw exceptions to avoid disrupting logout
     * 
     * @param string $userType
     * @param int $userId
     * @param Request $request
     * @param string $action 'logout' or 'logout_all'
     * @return bool Success status
     */
    public function logLogout(string $userType, int $userId, Request $request, string $action = 'logout'): bool
    {
        try {
            $data = [
                'user_type' => $userType,
                'user_id' => $userId,
                'action' => $action,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_info' => $this->extractDeviceInfo($request),
                'is_online' => 0
            ];

            $result = $this->repository->create($data);
            if ($userType == 'user') {
                $user = User::find($userId);
                $user->is_online = 0;
                $user->save();
            } else {
                $tech = Technician::find($userId);
                $tech->is_online = 0;
                $tech->save();
            }
            if ($result) {
                Log::info('Logout activity logged', [
                    'user_type' => $userType,
                    'user_id' => $userId,
                    'action' => $action,
                    'ip' => $request->ip()
                ]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            // CRITICAL: Never throw - just log the error
            Log::error('Failed to log logout activity', [
                'user_type' => $userType,
                'user_id' => $userId,
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get user activities
     * 
     * @param string $userType
     * @param int $userId
     * @param array $filters
     * @return array
     */
    public function getUserActivities(string $userType, int $userId, array $filters = []): array
    {
        try {
            $activities = $this->repository->getByUser($userType, $userId, $filters);

            return [
                'success' => true,
                'data' => $activities->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'action' => $activity->action,
                        'ip_address' => $activity->ip_address,
                        'device_info' => $activity->device_info,
                        'created_at' => $activity->created_at->toISOString(),
                    ];
                }),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to retrieve user activities', [
                'user_type' => $userType,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت تاریخچه ورود/خروج.',
                'data' => []
            ];
        }
    }

    /**
     * Get all activities (for admin panel)
     * 
     * @param array $filters
     * @param int $perPage
     * @return array
     */
    public function getAllActivities(array $filters = [], int $perPage = 20): array
    {
        try {
            $activities = $this->repository->getAll($filters, $perPage);

            return [
                'success' => true,
                'data' => $activities,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to retrieve all activities', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت فعالیت‌ها.',
                'data' => []
            ];
        }
    }

    /**
     * Extract device info from request
     * 
     * @param Request $request
     * @return string|null
     */
    protected function extractDeviceInfo(Request $request): ?string
    {
        try {
            $userAgent = $request->userAgent();

            // Simple device detection
            if (str_contains($userAgent, 'Mobile')) {
                return 'موبایل';
            } elseif (str_contains($userAgent, 'Tablet')) {
                return 'تبلت';
            } else {
                return 'دسکتاپ';
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Clean up old activities
     * 
     * @param int $daysToKeep
     * @return array
     */
    public function cleanupOldActivities(int $daysToKeep = 90): array
    {
        try {
            $deleted = $this->repository->deleteOldActivities($daysToKeep);

            return [
                'success' => true,
                'message' => "تعداد {$deleted} رکورد قدیمی حذف شد.",
                'deleted_count' => $deleted
            ];

        } catch (\Exception $e) {
            Log::error('Failed to cleanup old activities', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در پاکسازی رکوردهای قدیمی.',
                'deleted_count' => 0
            ];
        }
    }
}
