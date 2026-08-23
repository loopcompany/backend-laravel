<?php

namespace App\Services;

use App\DTOs\PollApplicationDTO;
use App\Models\PollApplication;
use App\Repositories\PollApplicationRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PollApplicationService
{
    public function __construct(
        private readonly PollApplicationRepository $repository
    ) {}

    /**
     * Submit a poll application
     */
    public function submitPoll(PollApplicationDTO $dto): array
    {
        // Check if user has already submitted
        if ($this->repository->hasUserSubmitted($dto->userId)) {
            throw new \InvalidArgumentException('شما قبلاً در نظرسنجی شرکت کرده‌اید.');
        }

        // Store the poll
        $poll = $this->repository->store($dto);

        return [
            'message' => 'نظرسنجی شما با موفقیت ثبت شد.',
            'poll' => $poll->toArray(),
        ];
    }

    /**
     * Get user's poll application
     */
    public function getUserPoll(int $userId): array
    {
        $poll = $this->repository->getUserPoll($userId);

        if (!$poll) {
            throw new ModelNotFoundException('شما هنوز در نظرسنجی شرکت نکرده‌اید.');
        }

        return [
            'poll' => $poll->toArray(),
        ];
    }

    /**
     * Check if user can participate in poll
     */
    public function canUserParticipate(int $userId): array
    {
        $hasSubmitted = $this->repository->hasUserSubmitted($userId);

        return [
            'can_participate' => !$hasSubmitted,
            'message' => $hasSubmitted 
                ? 'شما قبلاً در نظرسنجی شرکت کرده‌اید.' 
                : 'می‌توانید در نظرسنجی شرکت کنید.',
        ];
    }

    /**
     * Get poll statistics (admin only)
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Get recent testimonials for homepage display
     */
    public function getRecentTestimonials(int $limit = 3): array
    {
        try {
            $testimonials = $this->repository->getRecentTestimonials($limit);

            return [
                'success' => true,
                'message' => 'نظرات اخیر با موفقیت دریافت شدند',
                'testimonials' => $testimonials
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در دریافت نظرات',
                'testimonials' => []
            ];
        }
    }
}