<?php

namespace App\Repositories;

use App\DTOs\PollApplicationDTO;
use App\Models\PollApplication;

class PollApplicationRepository
{
    /**
     * Store a new poll application
     */
    public function store(PollApplicationDTO $dto): PollApplication
    {
        return PollApplication::create($dto->toArray());
    }

    /**
     * Check if user has already submitted a poll
     */
    public function hasUserSubmitted(int $userId): bool
    {
        return PollApplication::where('user_id', $userId)->exists();
    }

    /**
     * Get user's poll application
     */
    public function getUserPoll(int $userId): ?PollApplication
    {
        return PollApplication::where('user_id', $userId)->first();
    }

    /**
     * Get poll statistics (for admin purposes)
     */
    public function getStatistics(): array
    {
        $total = PollApplication::count();
        
        if ($total == 0) {
            return [
                'total_responses' => 0,
                'app_ratings' => [],
                'tech_ratings' => [],
                'support_ratings' => [],
            ];
        }

        return [
            'total_responses' => $total,
            'app_ratings' => $this->getRatingStatistics('app_rate'),
            'tech_ratings' => $this->getRatingStatistics('tech_rate'),
            'support_ratings' => $this->getRatingStatistics('support_rate'),
        ];
    }

    /**
     * Get rating statistics for a specific field
     */
    private function getRatingStatistics(string $field): array
    {
        $stats = PollApplication::selectRaw("$field, COUNT(*) as count")
            ->groupBy($field)
            ->pluck('count', $field)
            ->toArray();

        return [
            'خوب' => $stats['خوب'] ?? 0,
            'متوسط' => $stats['متوسط'] ?? 0,
            'ضعیف' => $stats['ضعیف'] ?? 0,
        ];
    }

    /**
     * Get recent testimonials with high ratings for display
     */
    public function getRecentTestimonials(int $limit = 3): array
    {
        return PollApplication::with('user')
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->where(function($query) {
                $query->where('app_rate', 'خوب')
                      ->orWhere('tech_rate', 'خوب')
                      ->orWhere('support_rate', 'خوب');
            })
            ->latest()
            ->take($limit)
            ->get()
            ->toArray();
    }
}