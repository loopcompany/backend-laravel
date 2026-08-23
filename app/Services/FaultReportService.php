<?php

namespace App\Services;

use App\Repositories\FaultReportRepository;
use Illuminate\Support\Facades\Log;

class FaultReportService
{
    public function __construct(
        private FaultReportRepository $faultReportRepo
    ) {}

    /**
     * ثبت گزارش خرابی جدید
     */
    public function createFaultReport(int $userId, array $data): array
    {
        try {
            $data['user_id'] = $userId;

            $faultReport = $this->faultReportRepo->create($data);

            // include related order address (if exists)
            $orderAddress = $faultReport->order?->user_address;

            return [
                'success' => true,
                'message' => 'گزارش خرابی با موفقیت ثبت شد.',
                'data' => [
                    'fault_report' => [
                        'id' => $faultReport->id,
                        'order_id' => $faultReport->order_id,
                        'product_name' => $faultReport->product_name,
                        'ordered_at' => $faultReport->ordered_at,
                        'delivered_at' => $faultReport->delivered_at,
                        'technician_code' => $faultReport->technician_code,
                        'paid_price' => $faultReport->paid_price,
                        'description' => $faultReport->description,
                        'created_at' => $faultReport->created_at->format('Y-m-d H:i:s'),
                        'order_address' => $orderAddress ? [
                            'city' => $orderAddress->city ?? null,
                            'region' => $orderAddress->region ?? null,
                            'address' => $orderAddress->address ?? null,
                            'mobile' => $orderAddress->mobile ?? null,
                            'full_name' => $orderAddress->full_name ?? null,
                        ] : null,
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('خطا در ثبت گزارش خرابی: ' . $e->getMessage(), [
                'user_id' => $userId,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت گزارش خرابی. لطفاً دوباره تلاش کنید.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }

    /**
     * دریافت لیست گزارش‌های خرابی کاربر
     */
    public function getUserFaultReports(int $userId): array
    {
        try {
            $faultReports = $this->faultReportRepo->getUserFaultReports($userId);

            $data = $faultReports->map(function ($report) {
                $orderAddress = $report->order?->user_address;

                return [
                    'id' => $report->id,
                    'order_id' => $report->order_id,
                    'product_name' => $report->product_name,
                    'ordered_at' => $report->ordered_at,
                    'delivered_at' => $report->delivered_at,
                    'technician_code' => $report->technician_code,
                    'paid_price' => $report->paid_price,
                    'description' => $report->description,
                    'order_address' => $orderAddress ? [
                        'city' => $orderAddress->city ?? null,
                        'region' => $orderAddress->region ?? null,
                        'address' => $orderAddress->address ?? null,
                        'mobile' => $orderAddress->mobile ?? null,
                        'full_name' => $orderAddress->full_name ?? null,
                    ] : null,
                    'created_at' => $report->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $report->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return [
                'success' => true,
                'data' => $data
            ];
        } catch (\Exception $e) {
            Log::error('خطا در دریافت گزارش‌های خرابی: ' . $e->getMessage(), [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت گزارش‌های خرابی.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }

    /**
     * دریافت جزئیات یک گزارش خرابی
     */
    public function getFaultReportDetail(int $userId, int $reportId): array
    {
        try {
            $faultReport = $this->faultReportRepo->findById($reportId);

            if (!$faultReport) {
                return [
                    'success' => false,
                    'message' => 'گزارش خرابی یافت نشد.',
                    'error_code' => 'FAULT_REPORT_NOT_FOUND'
                ];
            }

            // بررسی اینکه گزارش متعلق به کاربر است
            if ($faultReport->user_id !== $userId) {
                return [
                    'success' => false,
                    'message' => 'دسترسی غیرمجاز.',
                    'error_code' => 'UNAUTHORIZED'
                ];
            }

            $orderAddress = $faultReport->order?->user_address;

            return [
                'success' => true,
                'data' => [
                    'id' => $faultReport->id,
                    'order_id' => $faultReport->order_id,
                    'product_name' => $faultReport->product_name,
                    'ordered_at' => $faultReport->ordered_at,
                    'delivered_at' => $faultReport->delivered_at,
                    'technician_code' => $faultReport->technician_code,
                    'paid_price' => $faultReport->paid_price,
                    'description' => $faultReport->description,
                    'order_address' => $orderAddress ? [
                        'city' => $orderAddress->city ?? null,
                        'region' => $orderAddress->region ?? null,
                        'address' => $orderAddress->address ?? null,
                        'mobile' => $orderAddress->mobile ?? null,
                        'full_name' => $orderAddress->full_name ?? null,
                    ] : null,
                    'created_at' => $faultReport->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $faultReport->updated_at->format('Y-m-d H:i:s'),
                ]
            ];
        } catch (\Exception $e) {
            Log::error('خطا در دریافت جزئیات گزارش خرابی: ' . $e->getMessage(), [
                'user_id' => $userId,
                'report_id' => $reportId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت جزئیات گزارش خرابی.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ];
        }
    }
}
