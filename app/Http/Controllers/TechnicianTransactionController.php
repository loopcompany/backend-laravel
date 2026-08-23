<?php

namespace App\Http\Controllers;

use App\Models\TechnicianTransaction;
use App\Models\Settlement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class TechnicianTransactionController extends Controller
{
    /**
     * Get list of technician transactions with optional date filter
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $technicianId = auth('sanctum')->id();

        if (!$technicianId) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 401);
        }

        try {
            // Start query
            $query = TechnicianTransaction::where('technician_id', $technicianId)
                ->with(['order.user:id,name,phone']);

            // Filter by date if provided (convert Jalali to Gregorian)
            if ($request->has('from_date')) {
                $gregorianDate = $this->convertJalaliToGregorian($request->from_date);
                $query->whereDate('created_at', '>=', $gregorianDate);
            }

            if ($request->has('to_date')) {
                $gregorianDate = $this->convertJalaliToGregorian($request->to_date);
                $query->whereDate('created_at', '<=', $gregorianDate);
            }

            // Filter by date (single date)
            if ($request->has('date')) {
                $gregorianDate = $this->convertJalaliToGregorian($request->date);
                $query->whereDate('created_at', $gregorianDate);
            }

            // Order by newest first
            $transactions = $query->orderBy('created_at', 'desc')->get();

            // Format response
            $data = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'price' => $transaction->price,
                    'date' => $transaction->created_at->format('Y-m-d'),
                    'time' => $transaction->created_at->format('H:i:s'),
                    'customer_phone' => $transaction->order?->user?->phone ?? null,
                    'customer_name' => $transaction->order?->user?->name ?? null,
                    'order_id' => $transaction->order_id,
                    'type' => $transaction->type,
                    'type_label' => $this->getTypeLabel($transaction->type),
                    'status' => $transaction->status,
                    'status_label' => $this->getStatusLabel($transaction->status),
                    'commission' => $transaction->commission,
                    'referenceId' => $transaction->referenceId,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at->toDateTimeString(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'لیست تراکنش‌ها با موفقیت دریافت شد.',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست تراکنش‌ها.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get yearly income chart data (monthly breakdown)
     * برای نمودار میله‌ای درآمد سالانه
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function yearlyIncomeChart(Request $request): JsonResponse
    {
        $technician = auth('sanctum')->user();

        if (!$technician instanceof \App\Models\Technician) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 401);
        }

        try {
            // Log incoming request data
            \Log::info('Yearly Income Chart Request:', [
                'technician_id' => $technician->id,
                'request_data' => $request->all(),
            ]);

            // Get year parameter (default: current Jalali year)
            // Convert to integer because Jalalian library requires integer
            $jalaliYear = (int) $request->input('year', Jalalian::now()->getYear());
            
            \Log::info('Year parameter:', [
                'jalali_year' => $jalaliYear,
                'technician_id' => $technician->id,
            ]);
            
            // Validate year range
            if ($jalaliYear < 1300 || $jalaliYear > 1500) {
                return response()->json([
                    'success' => false,
                    'message' => 'سال وارد شده معتبر نیست. لطفاً یک سال شمسی معتبر وارد کنید (مثلاً 1403).',
                ], 400);
            }

            // Initialize monthly data (12 months)
            $monthlyData = [];
            $persianMonths = [
                1 => 'فروردین',
                2 => 'اردیبهشت',
                3 => 'خرداد',
                4 => 'تیر',
                5 => 'مرداد',
                6 => 'شهریور',
                7 => 'مهر',
                8 => 'آبان',
                9 => 'آذر',
                10 => 'دی',
                11 => 'بهمن',
                12 => 'اسفند',
            ];

            // Loop through all 12 months
            for ($month = 1; $month <= 12; $month++) {
                // Format month with leading zero (01, 02, ..., 12)
                $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
                
                // تبدیل اول و آخر ماه شمسی به میلادی
                $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
                
                // شروع ماه شمسی (روز اول)
                $jalaliStart = Jalalian::fromFormat('Y-m-d', "$jalaliYear-$monthStr-01");
                $gregorianStart = $jalaliStart->toCarbon()->startOfDay();
                
                // پایان ماه شمسی (روز آخر)
                // محاسبه تعداد روزهای ماه (با در نظر گرفتن کبیسه)
                if ($month <= 6) {
                    $lastDay = 31;
                } elseif ($month < 12) {
                    $lastDay = 30;
                } else {
                    // اسفند: 29 یا 30 روز بسته به کبیسه بودن
                    $lastDay = $jalaliStart->isLeapYear() ? 30 : 29;
                }
                
                $lastDayStr = str_pad($lastDay, 2, '0', STR_PAD_LEFT);
                $jalaliEnd = Jalalian::fromFormat('Y-m-d', "$jalaliYear-$monthStr-$lastDayStr");
                $gregorianEnd = $jalaliEnd->toCarbon()->endOfDay();

                // محاسبه درآمد کل از تراکنش‌های موفق (type = 1, status = 100)
                $query = TechnicianTransaction::where('technician_id', $technician->id)
                    ->whereIn('type', [1, '1'])
                    ->whereIn('status', [100, '100'])
                    ->whereBetween('created_at', [$gregorianStart, $gregorianEnd]);
                
                $totalIncome = $query->sum('price');
                $transactionCount = $query->count();

                // Log for debugging (only for month 8 - Aban)
                if ($month == 8) {
                    \Log::info("Month 8 (Aban) Details:", [
                        'gregorian_start' => $gregorianStart->format('Y-m-d H:i:s'),
                        'gregorian_end' => $gregorianEnd->format('Y-m-d H:i:s'),
                        'transaction_count' => $transactionCount,
                        'total_income' => $totalIncome,
                    ]);
                    
                    // Get actual transactions for this period
                    $transactions = TechnicianTransaction::where('technician_id', $technician->id)
                        ->whereBetween('created_at', [$gregorianStart, $gregorianEnd])
                        ->get(['id', 'type', 'status', 'price', 'created_at']);
                    
                    \Log::info("Month 8 Transactions:", [
                        'all_transactions' => $transactions->toArray(),
                    ]);
                }

                // محاسبه تسویه‌ها
                $totalSettlements = Settlement::where('technician_id', $technician->id)
                    ->whereBetween('created_at', [$gregorianStart, $gregorianEnd])
                    ->sum('amount');

                // درآمد خالص
                $netIncome = $totalIncome - $totalSettlements;

                $monthlyData[] = [
                    'month' => $month,
                    'month_name' => $persianMonths[$month],
                    'total_income' => (float) $totalIncome,
                    'total_settlements' => (float) $totalSettlements,
                    'net_income' => (float) $netIncome,
                ];
            }

            // Calculate yearly totals
            $yearlyTotalIncome = array_sum(array_column($monthlyData, 'total_income'));
            $yearlyTotalSettlements = array_sum(array_column($monthlyData, 'total_settlements'));
            $yearlyNetIncome = $yearlyTotalIncome - $yearlyTotalSettlements;

            return response()->json([
                'success' => true,
                'message' => 'داده‌های نمودار درآمد سالانه با موفقیت دریافت شد.',
                'data' => [
                    'year' => $jalaliYear,
                    'year_label' => "$jalaliYear",
                    'monthly_data' => $monthlyData,
                    'yearly_summary' => [
                        'total_income' => (float) $yearlyTotalIncome,
                        'total_settlements' => (float) $yearlyTotalSettlements,
                        'net_income' => (float) $yearlyNetIncome,
                    ],
                    'current_wallet' => (float) $technician->wallet,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Yearly Income Chart Error:', [
                'technician_id' => $technician->id ?? null,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت داده‌های نمودار.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get type label in Persian
     */
    private function getTypeLabel(int $type): string
    {
        return match ($type) {
            1 => 'واریز از سفارش',
            2 => 'برداشت',
            3 => 'کسر جریمه',
            default => 'نامشخص',
        };
    }

    /**
     * Get status label in Persian
     */
    private function getStatusLabel(int $status): string
    {
        return match ($status) {
            100 => 'موفق',
            -200 => 'ناموفق',
            0 => 'در انتظار',
            default => 'نامشخص',
        };
    }

    /**
     * Convert Jalali (Shamsi) date to Gregorian (Miladi)
     * 
     * @param string $jalaliDate Date in format YYYY-mm-dd (e.g., 1404-08-18)
     * @return string Gregorian date in format Y-m-d
     */
    private function convertJalaliToGregorian(string $jalaliDate): string
    {
        try {
            // Convert Jalali date to Gregorian using Jalalian
            // Format: 1404-08-18 or 1404/08/18
            $normalizedDate = str_replace('/', '-', $jalaliDate);
            $gregorian = Jalalian::fromFormat('Y-m-d', $normalizedDate)->toCarbon();
            
            return $gregorian->format('Y-m-d');
        } catch (\Exception $e) {
            // If conversion fails, return the original date
            return $jalaliDate;
        }
    }
}
