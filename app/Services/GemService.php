<?php

namespace App\Services;

use App\Repositories\GemActionRepository;
use App\Repositories\GemTransactionRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GemService
{
    public function __construct(
        private GemActionRepository $gemActionRepo,
        private GemTransactionRepository $gemTransactionRepo
    ) {}

    /**
     * دریافت لیست gem action های فعال برای گردونه شانس
     */
    public function getActiveGemActions(): array
    {
        try {
            $actions = $this->gemActionRepo->getActiveActions();

            return [
                'success' => true,
                'message' => 'لیست پاداش‌ها با موفقیت دریافت شد.',
                'data' => [
                    'actions' => $actions->map(function ($action) {
                        return [
                            'id' => $action->id,
                            'name' => $action->name,
                            'action_key' => $action->action_key,
                            'gems' => $action->gems,
                        ];
                    })
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در دریافت gem actions', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست پاداش‌ها',
                'error_code' => 'GET_ACTIONS_ERROR'
            ];
        }
    }

    /**
     * شرکت در گردونه شانس (هفته‌ای یک بار)
     */
    public function spinLuckyWheel(int $userId): array
    {
        try {
            // بررسی آیا کاربر این هفته قبلاً شرکت کرده
            $hasPlayedThisWeek = $this->gemTransactionRepo->hasUserPlayedThisWeek($userId);

            if ($hasPlayedThisWeek) {
                $lastTransaction = $this->gemTransactionRepo->getLastUserTransaction($userId);
                
                return [
                    'success' => false,
                    'message' => 'شما این هفته قبلاً در گردونه شانس شرکت کرده‌اید. لطفاً هفته آینده مجدداً تلاش کنید.',
                    'error_code' => 'ALREADY_PLAYED_THIS_WEEK',
                    'data' => [
                        'last_play_date' => $lastTransaction?->created_at,
                        'can_play_again_after' => now()->endOfWeek()->addDay()->startOfDay()
                    ]
                ];
            }

            // دریافت لیست gem action های فعال
            $activeActions = $this->gemActionRepo->getActiveActions();

            if ($activeActions->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'در حال حاضر هیچ پاداشی موجود نیست.',
                    'error_code' => 'NO_ACTIVE_ACTIONS'
                ];
            }

            // انتخاب تصادفی یک gem action
            $randomAction = $activeActions->random();

            DB::beginTransaction();

            try {
                // ثبت تراکنش
                $transaction = $this->gemTransactionRepo->createTransaction([
                    'user_id' => $userId,
                    'gem_action_id' => $randomAction->id,
                    'gems' => $randomAction->gems,
                ]);

                DB::commit();

                Log::info('کاربر در گردونه شانس شرکت کرد', [
                    'user_id' => $userId,
                    'gem_action_id' => $randomAction->id,
                    'gems_won' => $randomAction->gems,
                    'transaction_id' => $transaction->id
                ]);

                // محاسبه مجموع gem های کاربر
                $totalGems = $this->gemTransactionRepo->getTotalUserGems($userId);

                return [
                    'success' => true,
                    'message' => 'تبریک! شما ' . $randomAction->gems . ' امتیاز برنده شدید.',
                    'data' => [
                        'won_action' => [
                            'id' => $randomAction->id,
                            'name' => $randomAction->name,
                            'gems' => $randomAction->gems,
                        ],
                        'transaction_id' => $transaction->id,
                        'total_gems' => $totalGems,
                        'created_at' => $transaction->created_at
                    ]
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('خطا در شرکت در گردونه شانس', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در شرکت در گردونه شانس',
                'error_code' => 'SPIN_WHEEL_ERROR'
            ];
        }
    }

    /**
     * دریافت مجموع gem های یک کاربر
     */
    public function getUserTotalGems(int $userId): int
    {
        return $this->gemTransactionRepo->getTotalUserGems($userId);
    }

    /**
     * دریافت تاریخچه gem های یک کاربر
     */
    public function getUserGemHistory(int $userId, int $perPage = 20): array
    {
        try {
            $transactions = $this->gemTransactionRepo->getUserTransactions($userId, $perPage);
            $totalGems = $this->gemTransactionRepo->getTotalUserGems($userId);

            return [
                'success' => true,
                'message' => 'تاریخچه امتیازات با موفقیت دریافت شد.',
                'data' => [
                    'total_gems' => $totalGems,
                    'transactions' => $transactions->map(function ($transaction) {
                        return [
                            'id' => $transaction->id,
                            'gems' => $transaction->gems,
                            'action' => [
                                'id' => $transaction->gemAction->id,
                                'name' => $transaction->gemAction->name,
                                'action_key' => $transaction->gemAction->action_key,
                            ],
                            'created_at' => $transaction->created_at,
                        ];
                    }),
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'per_page' => $transactions->perPage(),
                        'total' => $transactions->total(),
                        'last_page' => $transactions->lastPage(),
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در دریافت تاریخچه gem', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت تاریخچه امتیازات',
                'error_code' => 'GET_HISTORY_ERROR'
            ];
        }
    }

    /**
     * بررسی آیا کاربر می‌تواند در گردونه شرکت کند
     */
    public function canUserPlayWheel(int $userId): array
    {
        $hasPlayedThisWeek = $this->gemTransactionRepo->hasUserPlayedThisWeek($userId);
        $lastTransaction = $this->gemTransactionRepo->getLastUserTransaction($userId);

        return [
            'success' => true,
            'data' => [
                'can_play' => !$hasPlayedThisWeek,
                'last_play_date' => $lastTransaction?->created_at,
                'next_available_date' => $hasPlayedThisWeek 
                    ? now()->endOfWeek()->addDay()->startOfDay()
                    : now(),
            ]
        ];
    }
}
