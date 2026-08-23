<?php

namespace App\Repositories;

use App\Models\TechnicianReview;
use Illuminate\Pagination\LengthAwarePaginator;

class ReviewRepository
{
    /**
     * ثبت نظر جدید
     */
    public function createReview(array $data): TechnicianReview
    {
        return TechnicianReview::create($data);
    }

    /**
     * بررسی اینکه آیا کاربر قبلاً برای این سفارش نظر ثبت کرده
     */
    public function hasUserReviewedOrder(int $userId, int $orderId): bool
    {
        return TechnicianReview::where('user_id', $userId)
            ->where('order_id', $orderId)
            ->exists();
    }

    /**
     * دریافت نظر کاربر برای یک سفارش
     */
    public function getUserOrderReview(int $userId, int $orderId): ?TechnicianReview
    {
        return TechnicianReview::where('user_id', $userId)
            ->where('order_id', $orderId)
            ->first();
    }

    /**
     * دریافت تمام نظرات یک تکنسین
     */
    public function getTechnicianReviews(int $technicianId, int $perPage = 20): LengthAwarePaginator
    {
        return TechnicianReview::where('technician_id', $technicianId)
            ->with(['user', 'order'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * دریافت نظرات یک کاربر
     */
    public function getUserReviews(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return TechnicianReview::where('user_id', $userId)
            ->with(['technician', 'order'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * محاسبه میانگین امتیازات یک تکنسین
     */
    public function getTechnicianAverageRatings(int $technicianId): array
    {
        $reviews = TechnicianReview::where('technician_id', $technicianId)->get();

        if ($reviews->isEmpty()) {
            return [
                'application_avg' => 0,
                'technician_avg' => 0,
                'support_avg' => 0,
                'overall_avg' => 0,
                'total_reviews' => 0,
            ];
        }

        return [
            'application_avg' => round($reviews->avg('application_rate'), 2),
            'technician_avg' => round($reviews->avg('technician_rate'), 2),
            'support_avg' => round($reviews->avg('support_rate'), 2),
            'overall_avg' => round($reviews->avg(function ($review) {
                return ($review->application_rate + $review->technician_rate + $review->support_rate) / 3;
            }), 2),
            'total_reviews' => $reviews->count(),
        ];
    }

    /**
     * دریافت یک نظر با شناسه
     */
    public function findReviewById(int $reviewId): ?TechnicianReview
    {
        return TechnicianReview::with(['user', 'technician', 'order'])->find($reviewId);
    }

    /**
     * حذف نظر
     */
    public function deleteReview(int $reviewId): bool
    {
        $review = TechnicianReview::find($reviewId);
        
        if (!$review) {
            return false;
        }

        return $review->delete();
    }
}
