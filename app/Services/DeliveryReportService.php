<?php

namespace App\Services;

use App\Repositories\DeliveryReportRepository;
use App\Repositories\OrderRepository;
use App\Repositories\TechnicianTransactionRepository;
use App\DTOs\DeliveryReportDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DeliveryReportService
{
    public function __construct(
        private DeliveryReportRepository $repo,
        private OrderRepository $orderRepo,
        private TechnicianTransactionRepository $technicianTransactionRepo
    ) {}

    /**
     * ایجاد گزارش تحویل جدید توسط تکنسین
     */
    public function createReport(DeliveryReportDTO $dto): array
    {
        try {
            // بررسی اینکه سفارش به تکنسین تعلق دارد
            $order = $this->orderRepo->getTechnicianOrderDetail($dto->technicianId, $dto->orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد یا به شما اختصاص داده نشده است.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // بررسی اینکه قبلاً گزارش ثبت نشده باشد
            $existingReport = $this->repo->findByOrderId($dto->orderId);
            if ($existingReport) {
                return [
                    'success' => false,
                    'message' => 'گزارش تحویل برای این سفارش قبلاً ثبت شده است.',
                    'error_code' => 'REPORT_ALREADY_EXISTS'
                ];
            }

            // تولید کد تایید 6 رقمی
            $verificationCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // ذخیره گزارش با کد هش شده
            $data = $dto->toArray();
            $data['verification_code'] = Hash::make($verificationCode);
            
            $report = $this->repo->create($data);

            Log::info('گزارش تحویل ثبت شد، در حال ارسال SMS', [
                'report_id' => $report->id,
                'order_id' => $dto->orderId,
                'user_phone' => $order->user->phone,
                'verification_code' => $verificationCode
            ]);

            // ارسال پیامک به کاربر
            $smsResult = $this->sendVerificationSMS($order->user->phone, $verificationCode);

            return [
                'success' => true,
                'message' => 'گزارش تحویل با موفقیت ثبت شد و کد تایید برای کاربر ارسال شد.',
                'data' => [
                    'report' => $report,
                    'verification_code_sent' => $smsResult,
                    'debug_code' => config('app.debug') ? $verificationCode : null
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create delivery report: ' . $e->getMessage(), [
                'orderId' => $dto->orderId,
                'technicianId' => $dto->technicianId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت گزارش تحویل',
                'error_code' => 'CREATE_REPORT_ERROR'
            ];
        }
    }

    /**
     * ارسال پیامک کد تایید به کاربر
     */
    private function sendVerificationSMS(string $phone, string $code): bool
    {
        try {
            $apiKey = config('smsir.api-key');
            $templateId = config('smsir.delivery_verification_template_id');
            
            Log::info('شروع ارسال SMS کد تحویل', [
                'phone' => $phone,
                'code' => $code,
                'template_id' => $templateId,
                'api_key_exists' => !empty($apiKey)
            ]);

            if (!$apiKey || !$templateId) {
                Log::error('کانفیگ SMS.ir برای ارسال کد تحویل یافت نشد', [
                    'api_key_exists' => !empty($apiKey),
                    'template_id' => $templateId
                ]);
                return false;
            }

            $curl = curl_init();
            
            $postData = [
                "mobile" => $phone,
                "templateId" => (int)$templateId,
                "parameters" => [
                    [
                        "name" => "CODE",
                        "value" => $code
                    ]
                ]
            ];

            Log::info('داده‌های ارسالی به SMS.ir', ['data' => $postData]);
            
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.sms.ir/v1/send/verify",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    "Accept: application/json",
                    "Content-Type: application/json",
                    "X-API-KEY: " . $apiKey
                ],
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);
            curl_close($curl);
            
            if ($err) {
                Log::error('خطای CURL در ارسال SMS کد تحویل', [
                    'error' => $err,
                    'phone' => $phone
                ]);
                return false;
            }

            $responseData = json_decode($response, true);
            
            if ($httpCode === 200 || $httpCode === 201) {
                Log::info('SMS کد تحویل با موفقیت ارسال شد', [
                    'phone' => $phone,
                    'http_code' => $httpCode,
                    'response' => $responseData
                ]);
                return true;
            } else {
                Log::error('خطا در ارسال SMS کد تحویل', [
                    'phone' => $phone,
                    'http_code' => $httpCode,
                    'response' => $responseData
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception در ارسال SMS کد تحویل', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'phone' => $phone
            ]);
            return false;
        }
    }

    /**
     * به‌روزرسانی گزارش تحویل توسط تکنسین
     */
    public function updateReport(int $reportId, int $technicianId, array $data): array
    {
        try {
            $report = $this->repo->findById($reportId);

            if (!$report) {
                return [
                    'success' => false,
                    'message' => 'گزارش تحویل یافت نشد.',
                    'error_code' => 'REPORT_NOT_FOUND'
                ];
            }

            // بررسی اینکه گزارش به تکنسین تعلق دارد
            if (!$this->repo->belongsToTechnician($reportId, $technicianId)) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به ویرایش این گزارش نیستید.',
                    'error_code' => 'UNAUTHORIZED'
                ];
            }

            // بررسی اینکه گزارش تایید نشده باشد
            if ($report->user_verified_at) {
                return [
                    'success' => false,
                    'message' => 'گزارش تایید شده توسط کاربر قابل ویرایش نیست.',
                    'error_code' => 'REPORT_ALREADY_VERIFIED'
                ];
            }

            // حذف فیلدهایی که نباید تغییر کنند
            unset($data['order_id'], $data['technician_id'], $data['user_verified_at']);

            $this->repo->update($reportId, $data);
            $updatedReport = $this->repo->findById($reportId);

            return [
                'success' => true,
                'message' => 'گزارش تحویل با موفقیت به‌روزرسانی شد.',
                'data' => [
                    'report' => $updatedReport
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update delivery report: ' . $e->getMessage(), [
                'reportId' => $reportId,
                'technicianId' => $technicianId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در به‌روزرسانی گزارش تحویل',
                'error_code' => 'UPDATE_REPORT_ERROR'
            ];
        }
    }

    /**
     * دریافت گزارش تحویل برای تکنسین
     */
    public function getReportByOrder(int $technicianId, int $orderId): array
    {
        try {
            $report = $this->repo->findByTechnicianAndOrder($technicianId, $orderId);

            if (!$report) {
                return [
                    'success' => false,
                    'message' => 'گزارش تحویل یافت نشد.',
                    'error_code' => 'REPORT_NOT_FOUND'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'report' => $report
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get delivery report: ' . $e->getMessage(), [
                'orderId' => $orderId,
                'technicianId' => $technicianId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت گزارش تحویل',
                'error_code' => 'GET_REPORT_ERROR'
            ];
        }
    }

    /**
     * دریافت لیست گزارش‌های تحویل تکنسین
     */
    public function getTechnicianReports(int $technicianId): array
    {
        try {
            $reports = $this->repo->getTechnicianReports($technicianId);

            return [
                'success' => true,
                'data' => [
                    'reports' => $reports
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get technician reports: ' . $e->getMessage(), [
                'technicianId' => $technicianId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست گزارش‌ها',
                'error_code' => 'GET_REPORTS_ERROR'
            ];
        }
    }

    /**
     * تایید گزارش توسط کاربر
     */
    public function verifyReportByUser(int $orderId, int $userId): array
    {
        try {
            // بررسی اینکه سفارش به کاربر تعلق دارد
            $order = $this->orderRepo->getUserOrder($userId, $orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد یا به شما تعلق ندارد.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            $report = $this->repo->findByOrderId($orderId);

            if (!$report) {
                return [
                    'success' => false,
                    'message' => 'گزارش تحویل یافت نشد.',
                    'error_code' => 'REPORT_NOT_FOUND'
                ];
            }

            if ($report->user_verified_at) {
                return [
                    'success' => false,
                    'message' => 'این گزارش قبلاً تایید شده است.',
                    'error_code' => 'REPORT_ALREADY_VERIFIED'
                ];
            }

            DB::beginTransaction();
            
            try {
                // 1. تایید گزارش تحویل
                $this->repo->verifyByUser($report->id);

                // 2. به‌روزرسانی سفارش (status = 2, finished_at)
                $this->orderRepo->updateOrder($orderId, [
                    'status' => 2,
                    'finished_at' => now()
                ]);

                // 3. ثبت تراکنش تکنسین (فقط در صورتی که پرداخت انجام شده باشد و تکنسین داشته باشد)
                if ($order->payment_status == 1 && $order->technician_id) {
                    // محاسبه مبلغ قبل از تخفیف (تخفیف ربطی به تکنسین ندارد)
                    $basePrice = $order->technician_price ?? $order->pakar_price ?? 0;
                    $totalPrice = $basePrice + ($order->extra_price ?? 0);
                    
                    // Load technician relation
                    if (!$order->relationLoaded('technician')) {
                        $order->load('technician');
                    }
                    
                    // محاسبه کمیسیون و سهم تکنسین
                    $commissionPercentage = $order->technician->commission ?? 80; // پیش‌فرض 80٪
                    $technicianShare = $totalPrice * $commissionPercentage / 100;

                    // ثبت تراکنش
                    $this->technicianTransactionRepo->createTransaction([
                        'technician_id' => $order->technician_id,
                        'order_id' => $orderId,
                        'price' => $technicianShare,
                        'commission' => $commissionPercentage,
                        'type' => 1, // واریز از سفارش
                        'status' => 100, // موفق
                        'description' => "دریافت وجه بابت سفارش #{$orderId}",
                    ]);

                    // افزایش موجودی کیف پول تکنسین
                    DB::table('technicians')
                        ->where('id', $order->technician_id)
                        ->increment('wallet', $technicianShare);

                    Log::info('Transaction created for technician', [
                        'order_id' => $orderId,
                        'technician_id' => $order->technician_id,
                        'total_price' => $totalPrice,
                        'technician_share' => $technicianShare,
                        'commission_percentage' => $commissionPercentage
                    ]);
                }

                DB::commit();

                return [
                    'success' => true,
                    'message' => 'گزارش تحویل با موفقیت تایید شد و سفارش نهایی گردید.',
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Failed to verify delivery report: ' . $e->getMessage(), [
                'orderId' => $orderId,
                'userId' => $userId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تایید گزارش تحویل',
                'error_code' => 'VERIFY_REPORT_ERROR'
            ];
        }
    }

    /**
     * تایید گزارش توسط تکنسین با کد تایید
     */
    public function verifyReportWithCode(int $orderId, int $technicianId, string $code): array
    {
        try {
            // دریافت گزارش
            $report = $this->repo->findByTechnicianAndOrder($technicianId, $orderId);

            if (!$report) {
                return [
                    'success' => false,
                    'message' => 'گزارش تحویل یافت نشد.',
                    'error_code' => 'REPORT_NOT_FOUND'
                ];
            }

            // بررسی اینکه قبلاً تایید نشده باشد
            if ($report->user_verified_at) {
                return [
                    'success' => false,
                    'message' => 'این گزارش قبلاً تایید شده است.',
                    'error_code' => 'REPORT_ALREADY_VERIFIED'
                ];
            }

            // بررسی صحت کد تایید
            if (!Hash::check($code, $report->verification_code)) {
                return [
                    'success' => false,
                    'message' => 'کد تایید اشتباه است.',
                    'error_code' => 'INVALID_VERIFICATION_CODE'
                ];
            }

            // دریافت سفارش
            $order = $this->orderRepo->getTechnicianOrderDetail($technicianId, $orderId);
            
            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            DB::beginTransaction();
            
            try {
                // 1. تایید گزارش تحویل
                $this->repo->verifyByUser($report->id);

                // 2. به‌روزرسانی سفارش (status = 2, finished_at)
                $this->orderRepo->updateOrder($orderId, [
                    'status' => 2,
                    'finished_at' => now()
                ]);

                // 3. ثبت تراکنش تکنسین (فقط در صورتی که پرداخت انجام شده باشد)
                if ($order->payment_status == 1) {
                    // محاسبه قیمت نهایی سفارش (بدون قیمت پلتفرم)
                    $technicianPrice = $order->payment_price(false);
                    
                    // محاسبه کمیسیون و سهم تکنسین
                    $commissionPercentage = $order->technician->commission ?? 80; // پیش‌فرض 80٪
                    $technicianShare = $technicianPrice * $commissionPercentage / 100;

                    // ثبت تراکنش
                    $this->technicianTransactionRepo->createTransaction([
                        'technician_id' => $technicianId,
                        'order_id' => $orderId,
                        'price' => $technicianShare,
                        'commission' => $commissionPercentage,
                        'type' => 1, // واریز از سفارش
                        'status' => 100, // موفق
                        'description' => "دریافت وجه بابت سفارش #{$orderId}",
                    ]);

                    // افزایش موجودی کیف پول تکنسین
                    DB::table('technicians')
                        ->where('id', $technicianId)
                        ->increment('wallet', $technicianShare);
                }

                DB::commit();

                return [
                    'success' => true,
                    'message' => 'گزارش تحویل با موفقیت تایید شد و سفارش نهایی گردید.',
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Failed to verify delivery report with code: ' . $e->getMessage(), [
                'orderId' => $orderId,
                'technicianId' => $technicianId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تایید گزارش تحویل',
                'error_code' => 'VERIFY_REPORT_ERROR'
            ];
        }
    }

    /**
     * ارسال مجدد کد تایید تحویل
     */
    public function resendVerificationCode(int $orderId, int $technicianId): array
    {
        try {
            // بررسی اینکه سفارش به تکنسین تعلق دارد
            $order = $this->orderRepo->getTechnicianOrderDetail($technicianId, $orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد یا به شما اختصاص داده نشده است.',
                    'error_code' => 'REPORT_NOT_FOUND'
                ];
            }

            // پیدا کردن گزارش تحویل
            $report = $this->repo->findByOrderId($orderId);
            
            if (!$report) {
                return [
                    'success' => false,
                    'message' => 'گزارش تحویل یافت نشد.',
                    'error_code' => 'REPORT_NOT_FOUND'
                ];
            }

            // بررسی اینکه گزارش به این تکنسین تعلق دارد
            if ($report->technician_id != $technicianId) {
                return [
                    'success' => false,
                    'message' => 'دسترسی غیرمجاز.',
                    'error_code' => 'UNAUTHORIZED'
                ];
            }

            // بررسی اینکه گزارش هنوز تایید نشده باشد
            if ($report->user_verified_at !== null) {
                return [
                    'success' => false,
                    'message' => 'این گزارش قبلاً تایید شده است.',
                    'error_code' => 'REPORT_ALREADY_VERIFIED'
                ];
            }

            // تولید کد تایید جدید 6 رقمی
            $verificationCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            // به‌روزرسانی کد هش شده در دیتابیس
            $this->repo->update($report->id, [
                'verification_code' => Hash::make($verificationCode)
            ]);

            Log::info('ارسال مجدد کد تایید تحویل', [
                'report_id' => $report->id,
                'order_id' => $orderId,
                'user_phone' => $order->user->phone,
                'verification_code' => $verificationCode
            ]);

            // ارسال پیامک به کاربر
            $smsResult = $this->sendVerificationSMS($order->user->phone, $verificationCode);

            return [
                'success' => true,
                'message' => 'کد تایید مجدداً برای کاربر ارسال شد.',
                'data' => [
                    'verification_code_sent' => $smsResult,
                    'debug_code' => config('app.debug') ? $verificationCode : null
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to resend verification code: ' . $e->getMessage(), [
                'orderId' => $orderId,
                'technicianId' => $technicianId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ارسال مجدد کد تایید',
                'error_code' => 'RESEND_CODE_ERROR'
            ];
        }
    }
}
