<?php

namespace App\Repositories;

use App\DTOs\ReportViolationDTO;
use App\Models\ReportViolation;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportViolationRepository
{
    /**
     * Store a new report violation
     */
    public function store(ReportViolationDTO $dto): ReportViolation
    {
        return ReportViolation::create($dto->toArray());
    }

    /**
     * Get user's report violations with pagination
     */
    public function getUserReports(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return ReportViolation::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get specific report violation by ID and user
     */
    public function getUserReport(int $userId, int $reportId): ?ReportViolation
    {
        return ReportViolation::where('user_id', $userId)
            ->where('id', $reportId)
            ->first();
    }

    /**
     * Update a report violation
     */
    public function update(int $reportId, array $data): bool
    {
        return ReportViolation::where('id', $reportId)->update($data);
    }

    /**
     * Delete a report violation
     */
    public function delete(int $reportId): bool
    {
        return ReportViolation::where('id', $reportId)->delete();
    }

    /**
     * Count user's total reports
     */
    public function getUserReportsCount(int $userId): int
    {
        return ReportViolation::where('user_id', $userId)->count();
    }

    /**
     * Get all reports for admin (with pagination)
     */
    public function getAllReports(int $perPage = 15): LengthAwarePaginator
    {
        return ReportViolation::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Search reports by criteria
     */
    public function searchReports(array $criteria, int $perPage = 10): LengthAwarePaginator
    {
        $query = ReportViolation::query();

        if (isset($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }

        if (isset($criteria['subject'])) {
            $query->where('subject', 'like', '%' . $criteria['subject'] . '%');
        }

        if (isset($criteria['date_from'])) {
            $query->where('created_at', '>=', $criteria['date_from']);
        }

        if (isset($criteria['date_to'])) {
            $query->where('created_at', '<=', $criteria['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}