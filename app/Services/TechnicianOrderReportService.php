<?php

namespace App\Services;

use App\DTOs\TechnicianOrderReportDTO;
use App\Models\Contact;
use App\Models\Technician;
use App\Repositories\TechnicianOrderReportRepository;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TechnicianOrderReportService
{
    public function __construct(
        protected TechnicianOrderReportRepository $reportRepository,
        protected OrderRepository $orderRepository,
        protected SmsService $smsService
    ) {
    }

    /**
     * ثبت گزارش توسط تکنسین
     */
    public function createReport(int $technicianId, TechnicianOrderReportDTO $dto): array
    {
        try {
            // بررسی اینکه آیا قبلاً گزارش ثبت شده
            if ($this->reportRepository->reportExistsForOrder($dto->order_id)) {
                return [
                    'success' => false,
                    'message' => 'برای این سفارش قبلاً گزارش ثبت شده است.',
                    'error_code' => 'REPORT_ALREADY_EXISTS'
                ];
            }

            // ایجاد گزارش
            $report = $this->reportRepository->create($technicianId, $dto->toArray());

            Log::info('گزارش سفارش توسط تکنسین ثبت شد', [
                'report_id' => $report->id,
                'technician_id' => $technicianId,
                'order_id' => $dto->order_id
            ]);

            // ارسال پیامک به کاربر
            $order = $this->orderRepository->findById($dto->order_id);
            if ($order && $order->user && $order->user->phone) {
                $this->smsService->sendProductStatusSubmittedByTechnicianToUser($order->user->phone, $dto->order_id);

                Log::info('پیامک ثبت وضعیت محصول به کاربر ارسال شد', [
                    'order_id' => $dto->order_id,
                    'user_phone' => $order->user->phone
                ]);
            }

            return [
                'success' => true,
                'message' => 'گزارش با موفقیت ثبت شد.',
                'data' => [
                    'report' => $this->formatReportData($report)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در ثبت گزارش سفارش', [
                'technician_id' => $technicianId,
                'order_id' => $dto->order_id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت گزارش. لطفاً دوباره تلاش کنید.',
                'error_code' => 'CREATE_REPORT_ERROR'
            ];
        }
    }

    /**
     * به‌روزرسانی گزارش توسط تکنسین
     */
    public function updateReport(int $technicianId, int $reportId, TechnicianOrderReportDTO $dto): array
    {
        try {
            // بررسی مالکیت
            if (!$this->reportRepository->isOwnedByTechnician($reportId, $technicianId)) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به ویرایش این گزارش نیستید.',
                    'error_code' => 'UNAUTHORIZED_ACCESS'
                ];
            }

            // بررسی اینکه گزارش تایید نشده باشد
            $report = $this->reportRepository->find($reportId);
            if ($report && $report->user_confirmed_at) {
                return [
                    'success' => false,
                    'message' => 'گزارش تایید شده قابل ویرایش نیست.',
                    'error_code' => 'REPORT_ALREADY_CONFIRMED'
                ];
            }

            // به‌روزرسانی
            $this->reportRepository->update($reportId, $dto->toArray());

            $updatedReport = $this->reportRepository->find($reportId);

            Log::info('گزارش سفارش توسط تکنسین به‌روزرسانی شد', [
                'report_id' => $reportId,
                'technician_id' => $technicianId
            ]);

            // ارسال پیامک به کاربر
            $order = $this->orderRepository->findById($dto->order_id);
            if ($order && $order->user && $order->user->phone) {
                $this->smsService->sendProductStatusSubmittedByTechnicianToUser($order->user->phone, $dto->order_id);

                Log::info('پیامک ثبت وضعیت محصول (ویرایش) به کاربر ارسال شد', [
                    'order_id' => $dto->order_id,
                    'user_phone' => $order->user->phone
                ]);
            }

            return [
                'success' => true,
                'message' => 'گزارش با موفقیت به‌روزرسانی شد.',
                'data' => [
                    'report' => $this->formatReportData($updatedReport)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در به‌روزرسانی گزارش', [
                'report_id' => $reportId,
                'technician_id' => $technicianId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در به‌روزرسانی گزارش. لطفاً دوباره تلاش کنید.',
                'error_code' => 'UPDATE_REPORT_ERROR'
            ];
        }
    }

    /**
     * مشاهده گزارش
     */
    public function getReport(int $reportId): array
    {
        try {
            $report = $this->reportRepository->find($reportId);

            if (!$report) {
                return [
                    'success' => false,
                    'message' => 'گزارش مورد نظر یافت نشد.',
                    'error_code' => 'REPORT_NOT_FOUND'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'report' => $this->formatReportData($report)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در دریافت گزارش', [
                'report_id' => $reportId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت گزارش.',
                'error_code' => 'GET_REPORT_ERROR'
            ];
        }
    }

    /**
     * مشاهده گزارش بر اساس سفارش
     */
    public function getReportByOrder(int $orderId): array
    {
        try {
            $report = $this->reportRepository->findByOrder($orderId);

            if (!$report) {
                return [
                    'success' => false,
                    'message' => 'گزارشی برای این سفارش یافت نشد.',
                    'error_code' => 'REPORT_NOT_FOUND'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'report' => $this->formatReportData($report)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در دریافت گزارش سفارش', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت گزارش.',
                'error_code' => 'GET_REPORT_ERROR'
            ];
        }
    }

    /**
     * تایید گزارش توسط کاربر
     */
    public function confirmReport(int $userId, int $reportId): array
    {
        try {
            // بررسی اینکه گزارش به سفارش کاربر تعلق دارد
            if (!$this->reportRepository->belongsToUser($reportId, $userId)) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به تایید این گزارش نیستید.',
                    'error_code' => 'UNAUTHORIZED_ACCESS'
                ];
            }

            // بررسی اینکه قبلاً تایید نشده باشد
            $report = $this->reportRepository->find($reportId);
            if ($report && $report->user_confirmed_at) {
                return [
                    'success' => false,
                    'message' => 'این گزارش قبلاً تایید شده است.',
                    'error_code' => 'ALREADY_CONFIRMED'
                ];
            }

            // تایید گزارش
            $confirmed = $this->reportRepository->confirmReport($reportId);

            if (!$confirmed) {
                return [
                    'success' => false,
                    'message' => 'خطا در تایید گزارش.',
                    'error_code' => 'CONFIRM_ERROR'
                ];
            }

            $updatedReport = $this->reportRepository->find($reportId);

            Log::info('گزارش سفارش توسط کاربر تایید شد', [
                'report_id' => $reportId,
                'user_id' => $userId
            ]);

            // ارسال پیامک به تکنسین
            if ($updatedReport->order && $updatedReport->order->technician && $updatedReport->order->technician->phone) {
                $smsService = app(\App\Services\SmsService::class);
                $smsService->sendOrderReportApprovedByUserToTechnician(
                    $updatedReport->order->technician->phone,
                    $updatedReport->order->id
                );

                Log::info('پیامک تایید Order Report به تکنسین ارسال شد', [
                    'order_id' => $updatedReport->order->id,
                    'technician_phone' => $updatedReport->order->technician->phone
                ]);
            }

            // ارسال پیامک به خود کاربر
            if ($updatedReport->order && $updatedReport->order->user && $updatedReport->order->user->phone) {
                $smsService = app(\App\Services\SmsService::class);
                $smsService->sendOrderReportApprovedByUserToSelf(
                    $updatedReport->order->user->phone,
                    $updatedReport->order->id
                );

                Log::info('پیامک تایید Order Report به کاربر ارسال شد', [
                    'order_id' => $updatedReport->order->id,
                    'user_phone' => $updatedReport->order->user->phone
                ]);
            }

            return [
                'success' => true,
                'message' => 'گزارش با موفقیت تایید شد.',
                'data' => [
                    'report' => $this->formatReportData($updatedReport)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در تایید گزارش', [
                'report_id' => $reportId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تایید گزارش. لطفاً دوباره تلاش کنید.',
                'error_code' => 'CONFIRM_REPORT_ERROR'
            ];
        }
    }

    /**
     * لیست گزارش‌های تکنسین
     */
    public function getTechnicianReports(int $technicianId, bool $confirmedOnly = false): array
    {
        try {
            $reports = $this->reportRepository->getTechnicianReports($technicianId, $confirmedOnly);

            return [
                'success' => true,
                'data' => [
                    'reports' => $reports->map(fn($report) => $this->formatReportData($report))->toArray(),
                    'total' => $reports->count()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در دریافت لیست گزارش‌های تکنسین', [
                'technician_id' => $technicianId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست گزارش‌ها.',
                'error_code' => 'GET_REPORTS_ERROR'
            ];
        }
    }

    /**
     * ارسال سفارش به لوپ (فقط اگر گزارش تایید شده باشد)
     */
    public function sendOrderToLoop(int $technicianId, int $orderId): array
    {
        try {
            // بررسی اینکه سفارش به تکنسین تعلق دارد
            if (!$this->orderRepository->isTechnicianOrderOwner($technicianId, $orderId)) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به انجام این عملیات نیستید.',
                    'error_code' => 'UNAUTHORIZED_ACCESS'
                ];
            }

            // بررسی اینکه گزارش تایید شده باشد
            if (!$this->reportRepository->isOrderReportConfirmed($orderId)) {
                return [
                    'success' => false,
                    'message' => 'گزارش سفارش توسط کاربر تایید نشده است. ابتدا باید کاربر گزارش را تایید کند.',
                    'error_code' => 'REPORT_NOT_CONFIRMED'
                ];
            }

            // بررسی اینکه قبلاً ارسال نشده باشد
            $order = $this->orderRepository->findWithReport($orderId);
            if ($order && $order->send_to_loop) {
                return [
                    'success' => false,
                    'message' => 'این سفارش قبلاً به لوپ ارسال شده است.',
                    'error_code' => 'ALREADY_SENT_TO_LOOP'
                ];
            }

            // به‌روزرسانی send_to_loop
            $updated = $this->orderRepository->updateSendToLoop($orderId);

            if (!$updated) {
                return [
                    'success' => false,
                    'message' => 'خطا در ارسال سفارش به لوپ.',
                    'error_code' => 'UPDATE_ERROR'
                ];
            }

            Log::info('سفارش به لوپ ارسال شد', [
                'order_id' => $orderId,
                'technician_id' => $technicianId,
                'sent_at' => now()
            ]);

            // ارسال پیامک به کاربر
            $order = $this->orderRepository->findWithReport($orderId);
            if ($order && $order->user && $order->user->phone) {
                $smsService = app(\App\Services\SmsService::class);
                $smsService->sendProductSentToLoopToUser($order->user->phone, $order->id);

                Log::info('پیامک اعزام به لوپ به کاربر ارسال شد', [
                    'order_id' => $order->id,
                    'user_phone' => $order->user->phone
                ]);
            }

            return [
                'success' => true,
                'message' => 'سفارش با موفقیت به لوپ ارسال شد.',
                'data' => [
                    'order_id' => $orderId,
                    'sent_to_loop_at' => now()->toISOString()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در ارسال سفارش به لوپ', [
                'order_id' => $orderId,
                'technician_id' => $technicianId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ارسال سفارش به لوپ. لطفاً دوباره تلاش کنید.',
                'error_code' => 'SEND_TO_LOOP_ERROR'
            ];
        }
    }

    public function doneInPlace(int $technicianId, int $orderId, string $technician_in_place_description): array
    {
        try {
            // بررسی اینکه سفارش به تکنسین تعلق دارد
            if (!$this->orderRepository->isTechnicianOrderOwner($technicianId, $orderId)) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به انجام این عملیات نیستید.',
                    'error_code' => 'UNAUTHORIZED_ACCESS'
                ];
            }


            // بررسی اینکه قبلاً ارسال نشده باشد
            $isDoneInPlace = $this->orderRepository->isDoneInPlace($orderId);

            if ($isDoneInPlace) {
                return [
                    'success' => false,
                    'message' => 'این سفارش قبلاً انجام شده است.',
                    'error_code' => 'ALREADY_DONE_IN_PLACE'
                ];
            }

            // به‌روزرسانی send_to_loop
            $updated = $this->orderRepository->updateDoneInPlace($orderId, $technician_in_place_description);

            if (!$updated) {
                return [
                    'success' => false,
                    'message' => 'خطا در عملیات انجام در محل',
                    'error_code' => 'UPDATE_ERROR'
                ];
            }

            Log::info('سفارش در محل انجام شد', [
                'order_id' => $orderId,
                'technician_id' => $technicianId,
                'sent_at' => now()
            ]);

            // ارسال پیامک به کاربر

            $adminContact = Contact::where('type', 'sms')->first();
            if ($adminContact) {
                $tech = Technician::findOrFail($technicianId);
                $this->smsService->sendOrderDoneInPlaceByTechnicianToAdmin($adminContact->link, $orderId, $tech->name);
            }
            return [
                'success' => true,
                'message' => 'سفارش با موفقیت در محل انجام شد.',
                'data' => [
                    'order_id' => $orderId,
                    'done_in_place' => now()->toISOString()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در عملیات انجام در محل', [
                'order_id' => $orderId,
                'technician_id' => $technicianId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در انجام سفارش. لطفاً دوباره تلاش کنید.',
                'error_code' => 'DONE_IN_PLACE_ERROR'
            ];
        }
    }
    /**
     * به‌روزرسانی اطلاعات لوپ (duration و loop_description)
     */
    public function updateOrderLoopInfo(int $technicianId, int $orderId, array $data): array
    {
        try {
            // بررسی اینکه سفارش به تکنسین تعلق دارد
            if (!$this->orderRepository->isTechnicianOrderOwner($technicianId, $orderId)) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به ویرایش این سفارش نیستید.',
                    'error_code' => 'UNAUTHORIZED_ACCESS'
                ];
            }

            // بررسی اینکه سفارش به لوپ ارسال شده باشد
            if (!$this->orderRepository->isSentToLoop($orderId)) {
                return [
                    'success' => false,
                    'message' => 'این سفارش هنوز به لوپ ارسال نشده است. ابتدا باید سفارش را به لوپ ارسال کنید.',
                    'error_code' => 'NOT_SENT_TO_LOOP'
                ];
            }

            // به‌روزرسانی اطلاعات
            $updated = $this->orderRepository->updateLoopInfo($orderId, $data);

            if (!$updated) {
                return [
                    'success' => false,
                    'message' => 'خطا در به‌روزرسانی اطلاعات.',
                    'error_code' => 'UPDATE_ERROR'
                ];
            }

            $order = $this->orderRepository->find($orderId);

            Log::info('اطلاعات لوپ سفارش به‌روزرسانی شد', [
                'order_id' => $orderId,
                'technician_id' => $technicianId,
                'data' => $data
            ]);

            return [
                'success' => true,
                'message' => 'اطلاعات سفارش با موفقیت به‌روزرسانی شد.',
                'data' => [
                    'order_id' => $orderId,
                    'duration' => $order->duration,
                    'loop_description' => $order->loop_description,
                    'updated_at' => $order->updated_at->toISOString()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در به‌روزرسانی اطلاعات لوپ', [
                'order_id' => $orderId,
                'technician_id' => $technicianId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در به‌روزرسانی اطلاعات. لطفاً دوباره تلاش کنید.',
                'error_code' => 'UPDATE_LOOP_INFO_ERROR'
            ];
        }
    }

    /**
     * ثبت توضیحات تکنسین و تاریخ/ساعت دلخواه (قبل از شروع سفارش)
     */
    public function setTechnicianDescription(int $technicianId, int $orderId, string $description, string $date, string $time, int $technicianPrice): array
    {
        // بررسی مالکیت سفارش
        if (!$this->orderRepository->isTechnicianOrderOwner($technicianId, $orderId)) {
            return [
                'success' => false,
                'message' => 'شما مجاز به ویرایش این سفارش نیستید.',
                'error_code' => 'UNAUTHORIZED_ACCESS'
            ];
        }

        // بررسی اینکه سفارش هنوز شروع نشده باشد
        if (!$this->orderRepository->isNotStarted($orderId)) {
            return [
                'success' => false,
                'message' => 'این سفارش قبلاً شروع شده است و امکان تغییر توضیحات و زمان وجود ندارد.',
                'error_code' => 'ORDER_ALREADY_STARTED'
            ];
        }

        // به‌روزرسانی (date و time و technician_price)
        $updated = $this->orderRepository->setTechnicianDescription($orderId, $description, $date, $time, $technicianPrice);

        if (!$updated) {
            Log::error('خطا در ثبت توضیحات و قیمت تکنسین', [
                'technician_id' => $technicianId,
                'order_id' => $orderId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت اطلاعات. لطفاً دوباره تلاش کنید.',
                'error_code' => 'UPDATE_ERROR'
            ];
        }

        Log::info('توضیحات و قیمت تکنسین با موفقیت ثبت شد', [
            'technician_id' => $technicianId,
            'order_id' => $orderId,
            'date' => $date,
            'time' => $time,
            'technician_price' => $technicianPrice
        ]);

        // دریافت سفارش به‌روزرسانی شده
        $order = $this->orderRepository->find($orderId);

        return [
            'success' => true,
            'message' => 'توضیحات و قیمت تکنسین با موفقیت ثبت شد.',
            'data' => [
                'order_id' => $orderId,
                'technician_des' => $order->technician_des,
                'date' => $order->date,
                'time' => $order->time,
                'technician_price' => $order->technician_price,
                'is_time_changed' => $order->is_time_changed,
                'created_at' => $order->created_at->toISOString(),
                'updated_at' => $order->updated_at->toISOString(),
            ]
        ];
    }

    /**
     * فرمت کردن داده‌های گزارش
     */
    private function formatReportData($report): array
    {
        return [
            'id' => $report->id,
            'order_id' => $report->order_id,
            'technician_id' => $report->technician_id,
            'name' => $report->name,
            'melicode' => $report->melicode,
            'product_name' => $report->product_name,
            'product_brand' => $report->product_brand,
            'product_model' => $report->product_model,
            'product_color' => $report->product_color,
            'product_serial_number' => $report->product_serial_number,
            'asset_label_code' => $report->asset_label_code,
            'accessories' => $report->accessories,
            'max_price' => $report->max_price,
            'min_price' => $report->min_price,
            'product_password' => $report->product_password,
            'user_reported_issues' => $report->user_reported_issues,
            'technician_reported_issues' => $report->technician_reported_issues,
            'technician_observed_issues' => $report->technician_observed_issues,
            'user_requested_services' => $report->user_requested_services,
            'is_confirmed' => $report->is_confirmed,
            'user_confirmed_at' => $report->user_confirmed_at?->toISOString(),
            'created_at' => $report->created_at?->toISOString(),
            'updated_at' => $report->updated_at?->toISOString(),
        ];
    }

    /**
     * ثبت زمان حرکت تکنسین (راه افتادن به سمت آدرس)
     */
    public function setOffToAddress(int $technicianId, int $orderId): array
    {
        try {
            // بررسی مالکیت سفارش
            if (!$this->orderRepository->isTechnicianOrderOwner($technicianId, $orderId)) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به انجام این عملیات نیستید.',
                    'error_code' => 'UNAUTHORIZED_ACCESS'
                ];
            }

            // دریافت سفارش
            $order = $this->orderRepository->find($orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش مورد نظر یافت نشد.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // بررسی اینکه قبلاً راه نیفتاده باشد
            if ($order->set_off_at) {
                return [
                    'success' => false,
                    'message' => 'زمان حرکت قبلاً ثبت شده است.',
                    'error_code' => 'ALREADY_SET_OFF'
                ];
            }

            // ثبت زمان حرکت
            $updated = $this->orderRepository->setSetOffAt($orderId);

            if (!$updated) {
                return [
                    'success' => false,
                    'message' => 'خطا در ثبت زمان حرکت.',
                    'error_code' => 'UPDATE_ERROR'
                ];
            }

            Log::info('زمان حرکت تکنسین ثبت شد', [
                'technician_id' => $technicianId,
                'order_id' => $orderId,
                'set_off_at' => now()
            ]);

            // ارسال پیامک به کاربر
            if ($order->user && $order->user->phone) {
                $this->smsService->sendTechnicianSetOffToUser($order->user->phone, $orderId);
            }

            return [
                'success' => true,
                'message' => 'زمان حرکت با موفقیت ثبت شد.',
                'data' => [
                    'order_id' => $orderId,
                    'set_off_at' => now()->toISOString()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در ثبت زمان حرکت تکنسین', [
                'technician_id' => $technicianId,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت زمان حرکت. لطفاً دوباره تلاش کنید.',
                'error_code' => 'SET_OFF_ERROR'
            ];
        }
    }

    /**
     * ثبت زمان رسیدن تکنسین به آدرس
     */
    public function arriveAtAddress(int $technicianId, int $orderId): array
    {
        try {
            // بررسی مالکیت سفارش
            if (!$this->orderRepository->isTechnicianOrderOwner($technicianId, $orderId)) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به انجام این عملیات نیستید.',
                    'error_code' => 'UNAUTHORIZED_ACCESS'
                ];
            }

            // دریافت سفارش
            $order = $this->orderRepository->find($orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش مورد نظر یافت نشد.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // بررسی اینکه راه افتاده باشد
            if (!$order->set_off_at) {
                return [
                    'success' => false,
                    'message' => 'ابتدا باید زمان حرکت را ثبت کنید.',
                    'error_code' => 'NOT_SET_OFF_YET'
                ];
            }

            // بررسی اینکه قبلاً نرسیده باشد
            if ($order->arrived_at) {
                return [
                    'success' => false,
                    'message' => 'زمان رسیدن قبلاً ثبت شده است.',
                    'error_code' => 'ALREADY_ARRIVED'
                ];
            }

            // ثبت زمان رسیدن
            $updated = $this->orderRepository->setArrivedAt($orderId);

            if (!$updated) {
                return [
                    'success' => false,
                    'message' => 'خطا در ثبت زمان رسیدن.',
                    'error_code' => 'UPDATE_ERROR'
                ];
            }

            Log::info('زمان رسیدن تکنسین ثبت شد', [
                'technician_id' => $technicianId,
                'order_id' => $orderId,
                'arrived_at' => now()
            ]);

            // ارسال پیامک به کاربر
            if ($order->user && $order->user->phone) {
                $this->smsService->sendTechnicianArrivedToUser($order->user->phone, $orderId);
            }

            return [
                'success' => true,
                'message' => 'زمان رسیدن با موفقیت ثبت شد.',
                'data' => [
                    'order_id' => $orderId,
                    'arrived_at' => now()->toISOString()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در ثبت زمان رسیدن تکنسین', [
                'technician_id' => $technicianId,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت زمان رسیدن. لطفاً دوباره تلاش کنید.',
                'error_code' => 'ARRIVE_ERROR'
            ];
        }
    }
}

