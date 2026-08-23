<?php

namespace App\Http\Controllers;

use App\Models\IncentivePlan;
use App\Models\Technician;
use Illuminate\Http\Request;

class TechnicianIncentivePlanController extends Controller
{
    /**
     * دریافت لیست طرح‌های تشویقی تکنسین
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // دریافت تکنسین لاگین شده
            $technician = auth('sanctum')->user();
            
            // بررسی احراز هویت
            if (!$technician instanceof Technician) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر احراز هویت نشده است.',
                ], 401);
            }

            // فیلتر بر اساس وضعیت (اختیاری)
            $status = $request->query('status');

            // دریافت طرح‌های تشویقی تکنسین
            $query = IncentivePlan::where('technician_id', $technician->id)
                ->orderBy('created_at', 'desc');

            // اعمال فیلتر وضعیت (در صورت وجود)
            if ($status !== null && in_array($status, [0, 1, '0', '1'])) {
                $query->where('status', (int)$status);
            }

            $plans = $query->get()->map(function ($plan) {
                $isExpired = $plan->end_at && now()->isAfter($plan->end_at);
                
                return [
                    'id' => $plan->id,
                    'description' => $plan->description,
                    'end_at' => $plan->end_at?->format('Y-m-d H:i:s'),
                    'status' => $plan->status,
                    'status_label' => $isExpired ? 'Expired' : $this->getStatusLabel($plan->status),
                    'created_at' => $plan->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $plan->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            // آمار طرح‌ها
            $statistics = [
                'total' => IncentivePlan::where('technician_id', $technician->id)->count(),
                'active' => IncentivePlan::where('technician_id', $technician->id)
                    ->where('status', IncentivePlan::STATUS_PENDING)
                    ->where(function($q) {
                        $q->whereNull('end_at')->orWhere('end_at', '>', now());
                    })
                    ->count(),
                'used' => IncentivePlan::where('technician_id', $technician->id)
                    ->where('status', IncentivePlan::STATUS_USED)->count(),
                'expired' => IncentivePlan::where('technician_id', $technician->id)
                    ->whereNotNull('end_at')
                    ->where('end_at', '<', now())->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'لیست طرح‌های تشویقی با موفقیت دریافت شد.',
                'data' => $plans,
                'statistics' => $statistics,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست طرح‌های تشویقی.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * نمایش جزئیات یک طرح تشویقی خاص
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int $id)
    {
        try {
            // دریافت تکنسین لاگین شده
            $technician = auth('sanctum')->user();
            
            // بررسی احراز هویت
            if (!$technician instanceof Technician) {
                return response()->json([
                    'success' => false,
                    'message' => 'کاربر احراز هویت نشده است.',
                ], 401);
            }

            // جستجوی طرح با بررسی مالکیت
            $plan = IncentivePlan::where('id', $id)
                ->where('technician_id', $technician->id)
                ->first();

            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'طرح تشویقی مورد نظر یافت نشد.',
                ], 404);
            }

            $isExpired = $plan->end_at && now()->isAfter($plan->end_at);

            return response()->json([
                'success' => true,
                'message' => 'جزئیات طرح تشویقی با موفقیت دریافت شد.',
                'data' => [
                    'id' => $plan->id,
                    'description' => $plan->description,
                    'end_at' => $plan->end_at?->format('Y-m-d H:i:s'),
                    'status' => $plan->status,
                    'status_label' => $isExpired ? 'Expired' : $this->getStatusLabel($plan->status),
                    'created_at' => $plan->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $plan->updated_at->format('Y-m-d H:i:s'),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت جزئیات طرح تشویقی.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * دریافت برچسب وضعیت به فارسی
     * 
     * @param int $status
     * @return string
     */
    private function getStatusLabel(int $status): string
    {
        return match($status) {
            IncentivePlan::STATUS_PENDING => 'Active',
            IncentivePlan::STATUS_USED => 'Used',
            default => 'Unknown',
        };
    }
}
