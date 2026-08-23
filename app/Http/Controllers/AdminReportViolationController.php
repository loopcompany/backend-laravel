<?php

namespace App\Http\Controllers;

use App\Models\AdminReportViolation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminReportViolationController extends Controller
{
    /**
     * دریافت لیست گزارش‌های تخلف برای تکنسین
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $technician = auth('sanctum')->user();

        if (!$technician instanceof \App\Models\Technician) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 401);
        }

        try {
            $reports = AdminReportViolation::where('technician_id', $technician->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($report) {
                    return [
                        'id' => $report->id,
                        'title' => $report->title,
                        'description' => $report->description,
                        'technician_response' => $report->technician_response,
                        'can_reply' => $report->can_reply,
                        'has_response' => $report->hasResponse(),
                        'response_status_label' => $report->response_status_label,
                        'created_at' => $report->created_at->toISOString(),
                        'updated_at' => $report->updated_at->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'لیست گزارش‌های تخلف با موفقیت دریافت شد.',
                'data' => $reports,
                'total' => $reports->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست گزارش‌های تخلف.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * دریافت جزئیات یک گزارش تخلف
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $technician = auth('sanctum')->user();

        if (!$technician instanceof \App\Models\Technician) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 401);
        }

        try {
            $report = AdminReportViolation::where('technician_id', $technician->id)
                ->where('id', $id)
                ->first();

            if (!$report) {
                return response()->json([
                    'success' => false,
                    'message' => 'گزارش تخلف یافت نشد.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'جزئیات گزارش تخلف با موفقیت دریافت شد.',
                'data' => [
                    'id' => $report->id,
                    'title' => $report->title,
                    'description' => $report->description,
                    'technician_response' => $report->technician_response,
                    'can_reply' => $report->can_reply,
                    'has_response' => $report->hasResponse(),
                    'response_status_label' => $report->response_status_label,
                    'created_at' => $report->created_at->toISOString(),
                    'updated_at' => $report->updated_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت جزئیات گزارش تخلف.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ارسال پاسخ به گزارش تخلف
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function reply(Request $request, int $id): JsonResponse
    {
        $technician = auth('sanctum')->user();

        if (!$technician instanceof \App\Models\Technician) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 401);
        }

        try {
            // پیدا کردن گزارش تخلف
            $report = AdminReportViolation::where('technician_id', $technician->id)
                ->where('id', $id)
                ->first();

            if (!$report) {
                return response()->json([
                    'success' => false,
                    'message' => 'گزارش تخلف یافت نشد.',
                ], 404);
            }

            // بررسی امکان پاسخ‌دهی
            if ($report->can_reply !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'امکان پاسخ‌دهی به این گزارش وجود ندارد.',
                ], 403);
            }

            // بررسی پاسخ قبلی
            if ($report->hasResponse()) {
                return response()->json([
                    'success' => false,
                    'message' => 'شما قبلاً به این گزارش پاسخ داده‌اید.',
                ], 400);
            }

            // اعتبارسنجی
            $validator = Validator::make($request->all(), [
                'response' => 'required|string|max:5000',
            ], [
                'response.required' => 'پاسخ الزامی است.',
                'response.string' => 'پاسخ باید متن باشد.',
                'response.max' => 'پاسخ نباید بیشتر از 5000 کاراکتر باشد.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در اعتبارسنجی.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // ثبت پاسخ
            $report->update([
                'technician_response' => $request->response,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'پاسخ شما با موفقیت ثبت شد.',
                'data' => [
                    'id' => $report->id,
                    'title' => $report->title,
                    'description' => $report->description,
                    'technician_response' => $report->technician_response,
                    'can_reply' => $report->can_reply,
                    'has_response' => $report->hasResponse(),
                    'response_status_label' => $report->response_status_label,
                    'created_at' => $report->created_at->toISOString(),
                    'updated_at' => $report->updated_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ثبت پاسخ.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
