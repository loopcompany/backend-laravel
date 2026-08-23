<?php

namespace App\Services;

use App\DTOs\ReportViolationDTO;
use App\Models\ReportViolation;
use App\Repositories\ReportViolationRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportViolationService
{
    public function __construct(
        private readonly ReportViolationRepository $repository
    ) {}

    /**
     * Submit a new report violation
     */
    public function submitReport(ReportViolationDTO $dto): array
    {
        $report = $this->repository->store($dto);

        return [
            'message' => 'گزارش تخلف شما با موفقیت ثبت شد.',
            'report' => $report->toArray(),
        ];
    }

    /**
     * Get user's report violations
     */
    public function getUserReports(int $userId, int $page = 1, int $perPage = 10): array
    {
        $reports = $this->repository->getUserReports($userId, $perPage);
        $totalCount = $this->repository->getUserReportsCount($userId);

        return [
            'reports' => $reports->items(),
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
            'total_reports' => $totalCount,
        ];
    }

    /**
     * Get specific report by ID
     */
    public function getUserReport(int $userId, int $reportId): array
    {
        $report = $this->repository->getUserReport($userId, $reportId);

        if (!$report) {
            throw new ModelNotFoundException('گزارش مورد نظر یافت نشد.');
        }

        return [
            'report' => $report->toArray(),
        ];
    }

    /**
     * Update a report violation
     */
    public function updateReport(int $userId, int $reportId, ReportViolationDTO $dto): array
    {
        // Check if report belongs to user
        $report = $this->repository->getUserReport($userId, $reportId);
        
        if (!$report) {
            throw new ModelNotFoundException('گزارش مورد نظر یافت نشد.');
        }

        // Update report (excluding user_id)
        $updateData = $dto->toArray();
        unset($updateData['user_id']);
        
        $this->repository->update($reportId, $updateData);

        // Get updated report
        $updatedReport = $this->repository->getUserReport($userId, $reportId);

        return [
            'message' => 'گزارش با موفقیت بروزرسانی شد.',
            'report' => $updatedReport->toArray(),
        ];
    }

    /**
     * Delete a report violation
     */
    public function deleteReport(int $userId, int $reportId): array
    {
        // Check if report belongs to user
        $report = $this->repository->getUserReport($userId, $reportId);
        
        if (!$report) {
            throw new ModelNotFoundException('گزارش مورد نظر یافت نشد.');
        }

        $this->repository->delete($reportId);

        return [
            'message' => 'گزارش با موفقیت حذف شد.',
        ];
    }

    /**
     * Search reports
     */
    public function searchReports(int $userId, array $criteria, int $perPage = 10): array
    {
        // Add user filter to criteria
        $criteria['user_id'] = $userId;
        
        $reports = $this->repository->searchReports($criteria, $perPage);

        return [
            'reports' => $reports->items(),
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ];
    }

    /**
     * Get all reports for admin
     */
    public function getAllReports(int $perPage = 15): array
    {
        $reports = $this->repository->getAllReports($perPage);

        return [
            'reports' => $reports->items(),
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ];
    }
}