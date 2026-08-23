<?php

namespace App\Services;

use App\Repositories\ReviewRepository;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class ReviewService
{
    public function __construct(
        protected ReviewRepository $reviewRepo
    ) {}

    /**
     * ثبت نظر برای یک سفارش
     */
    public function submitReview(int $userId, array $data): array
    {
        try {
            // بررسی اینکه آیا سفارش متعلق به کاربر است
            $order = Order::where('id', $data['order_id'])
                ->where('user_id', $userId)
                ->first();

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد یا متعلق به شما نیست.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // بررسی اینکه آیا سفارش تکمیل شده است (status = 2)
            if ($order->status != 2) {
                return [
                    'success' => false,
                    'message' => 'فقط می‌توانید برای سفارشات تکمیل شده نظر ثبت کنید.',
                    'error_code' => 'ORDER_NOT_COMPLETED'
                ];
            }

            // بررسی اینکه آیا تکنسین سفارش با تکنسین نظر یکی است
            if ($order->technician_id != $data['technician_id']) {
                return [
                    'success' => false,
                    'message' => 'تکنسین وارد شده با تکنسین سفارش مطابقت ندارد.',
                    'error_code' => 'TECHNICIAN_MISMATCH'
                ];
            }

            // بررسی اینکه آیا قبلاً نظر ثبت شده
            if ($this->reviewRepo->hasUserReviewedOrder($userId, $data['order_id'])) {
                return [
                    'success' => false,
                    'message' => 'شما قبلاً برای این سفارش نظر ثبت کرده‌اید.',
                    'error_code' => 'REVIEW_ALREADY_EXISTS'
                ];
            }

            // ثبت نظر
            $reviewData = array_merge($data, ['user_id' => $userId]);
            $review = $this->reviewRepo->createReview($reviewData);

            Log::info('نظر جدید ثبت شد', [
                'user_id' => $userId,
                'order_id' => $data['order_id'],
                'technician_id' => $data['technician_id'],
                'review_id' => $review->id
            ]);

            return [
                'success' => true,
                'message' => 'نظر شما با موفقیت ثبت شد.',
                'data' => [
                    'review' => $review
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در ثبت نظر', [
                'user_id' => $userId,
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت نظر',
                'error_code' => 'REVIEW_SUBMISSION_ERROR'
            ];
        }
    }

    /**
     * دریافت نظرات یک تکنسین
     */
    public function getTechnicianReviews(int $technicianId, int $perPage = 20): array
    {
        try {
            $reviews = $this->reviewRepo->getTechnicianReviews($technicianId, $perPage);
            $averages = $this->reviewRepo->getTechnicianAverageRatings($technicianId);

            return [
                'success' => true,
                'data' => [
                    'reviews' => $reviews->items(),
                    'averages' => $averages,
                    'pagination' => [
                        'current_page' => $reviews->currentPage(),
                        'last_page' => $reviews->lastPage(),
                        'per_page' => $reviews->perPage(),
                        'total' => $reviews->total(),
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در دریافت نظرات تکنسین', [
                'technician_id' => $technicianId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت نظرات',
                'error_code' => 'GET_REVIEWS_ERROR'
            ];
        }
    }

    /**
     * دریافت نظرات یک کاربر
     */
    public function getUserReviews(int $userId, int $perPage = 20): array
    {
        try {
            $reviews = $this->reviewRepo->getUserReviews($userId, $perPage);

            return [
                'success' => true,
                'data' => [
                    'reviews' => $reviews->items(),
                    'pagination' => [
                        'current_page' => $reviews->currentPage(),
                        'last_page' => $reviews->lastPage(),
                        'per_page' => $reviews->perPage(),
                        'total' => $reviews->total(),
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در دریافت نظرات کاربر', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت نظرات',
                'error_code' => 'GET_USER_REVIEWS_ERROR'
            ];
        }
    }
}
