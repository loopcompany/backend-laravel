<?php

namespace App\Services;

use App\Repositories\OrderRepository;
use App\Repositories\ChatRepository;
use App\Repositories\TechnicianTransactionRepository;
use App\Helpers\Helper;
use App\Models\DiscountCode;
use App\Models\Technician;
use App\Models\Order;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\FacadesLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private OrderRepository $repo,
        private ChatRepository $chatRepo,
        private TechnicianTransactionRepository $technicianTransactionRepo
    ) {
    }

    public function uploadFile(UploadedFile $file): string
    {
        try {
            $filePath = $file->store('order', 'public');
            return $filePath;
        } catch (\Exception $e) {
            Log::error('Order file upload failed: ' . $e->getMessage());
            throw new \Exception('خطا در آپلود فایل.');
        }
    }

    public function uploadMultipleFiles(array $files): array
    {
        $uploadedPaths = [];

        foreach ($files as $file) {
            try {
                $filePath = $file->store('order', 'public');
                $uploadedPaths[] = $filePath;
            } catch (\Exception $e) {
                Log::error('Order file upload failed: ' . $e->getMessage());
            }
        }

        return $uploadedPaths;
    }

    public function submitOrder(array $data, int $userId): array
    {
        // لاگ کامل داده‌های ورودی از اپ
        Log::info('=== ORDER SUBMISSION - Raw Input Data ===', [
            'user_id' => $userId,
            'full_data_structure' => $data,
            'data_keys' => array_keys($data),
            'steps_structure' => $data['steps'] ?? 'NOT_PROVIDED',
            'file_paths' => $data['file_paths'] ?? 'file_paths NOT_PROVIDED',
            'has_service_schedule_field' => isset($data['service_schedule']),
            'steps_count' => is_array($data['steps'] ?? null) ? count($data['steps']) : 0
        ]);

        // بررسی کد تخفیف
        $discountCode = null;
        if (!empty($data['discount_code'])) {
            $discountResult = $this->validateDiscountCode(
                $data['discount_code'],
                $data['category_id'],
                $userId
            );

            if (!$discountResult['valid']) {
                return [
                    'success' => false,
                    'message' => $discountResult['message'],
                    'error_code' => 'INVALID_DISCOUNT_CODE'
                ];
            }

            $discountCode = $discountResult['discount_code'];
        }

        // تنظیم تعداد تکنسین‌ها
        $genderCounts = $this->normalizeGenderCounts(
            $data['female_count'] ?? 0,
            $data['male_count'] ?? 0,
            $data['unspecified_count'] ?? 0
        );

        // ایجاد سفارش
        $orderData = [
            'user_id' => $userId,
            'user_address_id' => $data['address_id'],
            'category_id' => $data['category_id'],
            'pakar_price' => $data['total_price'],
            'is_urgent' => $data['is_urgent'] ?? false,
            'is_fixed' => $data['is_fixed'] ?? false,
            'image_path' => $data['image_path'] ?? null,
            'des' => $data['description'] ?? null,
            'date' => $data['date'],
            'time' => $data['time'],
            'platform' => $data['platform'],
            'female_count' => $genderCounts['female'],
            'male_count' => $genderCounts['male'],
            'unspecified_count' => $genderCounts['unspecified'],
        ];
        // اضافه کردن اطلاعات service_schedule (برای سازمان‌ها)
        $serviceScheduleData = $this->extractServiceScheduleFromSteps($data['steps'] ?? []);

        Log::info('=== SERVICE_SCHEDULE EXTRACTION RESULT ===', [
            'serviceScheduleData' => $serviceScheduleData,
            'is_empty' => empty($serviceScheduleData),
            'array_count' => is_array($serviceScheduleData) ? count($serviceScheduleData) : 'NOT_ARRAY'
        ]);

        if (!empty($serviceScheduleData)) {
            $parsedSchedule = $this->parseServiceSchedule($serviceScheduleData);

            Log::info('Service Schedule Parsed from Steps', [
                'original' => $serviceScheduleData,
                'parsed' => $parsedSchedule
            ]);

            $orderData = array_merge($orderData, $parsedSchedule);

            // هماهنگ کردن date و time اصلی سفارش با service_schedule
            $serviceDate =
                $parsedSchedule['service_schedule_short_date']
                ?? $parsedSchedule['service_schedule_long_date']
                ?? null;

            $serviceTime =
                $parsedSchedule['service_schedule_short_time']
                ?? $parsedSchedule['service_schedule_long_time']
                ?? null;

            if ($serviceDate) {
                $orderData['date'] = $serviceDate;
            }

            if ($serviceTime) {
                $orderData['time'] = $serviceTime;
            }

            Log::info('Order date/time overridden from service schedule', [
                'date' => $orderData['date'],
                'time' => $orderData['time'],
            ]);
        } else {
            Log::info('No service_schedule data found in steps', [
                'request_data_keys' => array_keys($data)
            ]);
        }

        $order = $this->repo->create($orderData);

        // ذخیره جزئیات سفارش (steps)
        if (!empty($data['steps'])) {
            $this->saveOrderDetails($order->id, $data['steps']);
        }
        if (!empty($data['file_paths'])) {
            $this->saveOrderGallery($order->id, $data['file_paths']);
        }

        // ثبت استفاده از کد تخفیف
        if ($discountCode) {
            $this->applyDiscountCode($order->id, $discountCode);
        }

        // ارسال پیامک تأیید سفارش
        $this->sendOrderConfirmationSms($order, $data['user']);

        return [
            'success' => true,
            'message' => 'سفارش شما با موفقیت ثبت شد.',
            'data' => [
                'order_id' => $order->id,
                'order' => $order
            ]
        ];
    }

    private function validateDiscountCode(string $code, int $categoryId, int $userId): array
    {
        $result = Helper::discount_check($code, $categoryId, $userId);
        $responseArr = $result->getData(true);

        if (!$responseArr['status']) {
            return [
                'valid' => false,
                'message' => $responseArr['error']
            ];
        }

        $discountCode = DiscountCode::find($responseArr['discount_code_id']);

        if (!$discountCode) {
            return [
                'valid' => false,
                'message' => 'مشکل در دریافت اطلاعات کد تخفیف'
            ];
        }

        return [
            'valid' => true,
            'discount_code' => $discountCode
        ];
    }

    private function normalizeGenderCounts(int $female, int $male, int $unspecified): array
    {
        // اگر مجموع صفر یا منفی باشد، یک تکنسین نامشخص تنظیم می‌کنیم
        if ($female + $male + $unspecified <= 0) {
            return [
                'female' => 0,
                'male' => 0,
                'unspecified' => 1
            ];
        }

        return [
            'female' => $female,
            'male' => $male,
            'unspecified' => $unspecified
        ];
    }

    private function saveOrderDetails(int $orderId, array $steps): void
    {
        $validTypes = ['checkbox', 'radioButton', 'counter', 'input'];

        foreach ($steps as $step) {
            foreach ($step as $field) {
                if (in_array($field['type'], $validTypes)) {
                    foreach ($field['field_details'] as $fieldDetail) {
                        if (!empty($fieldDetail['value'])) {
                            $this->repo->createOrderDetail([
                                'order_id' => $orderId,
                                'field_id' => $field['id'],
                                'field_detail_id' => $fieldDetail['id'],
                                'price' => $fieldDetail['price'] ?? 0,
                                'value' => $fieldDetail['value'],
                                'user_descriptions' => $fieldDetail['user_descriptions'],
                            ]);
                        }
                    }
                }
            }
        }
    }
    private function saveOrderGallery(int $orderId, array $file_paths): void
    {
        foreach ($file_paths as $file_path) {
            Log::info($file_path);
            $this->repo->createOrderGallery([
                'order_id' => $orderId,
                'image_path' => $file_path,
            ]);
        }
    }

    private function applyDiscountCode(int $orderId, DiscountCode $discountCode): void
    {
        $this->repo->createDiscountUse([
            'order_id' => $orderId,
            'user_id' => $discountCode->user_id,
            'discount_code_id' => $discountCode->id,
        ]);

        $discountCode->count -= 1;
        $discountCode->save();
    }

    private function sendOrderConfirmationSms(object $order, object $user): void
    {
        try {
            Helper::send_sms(
                $user->phone,
                '957924',
                ['NAME', 'ID'],
                [$user->name . ' ' . $user->last_name, $order->id]
            );
        } catch (\Exception $e) {
            Log::error('Order confirmation SMS failed: ' . $e->getMessage());
            // ادامه می‌دهیم چون خطای SMS نباید سفارش را لغو کند
        }
    }

    public function checkDiscount(string $code, int $categoryId, int $userId): array
    {
        $discountCode = \App\Models\DiscountCode::where('code', $code)->first();

        if (!$discountCode) {
            return [
                'success' => false,
                'message' => 'کد تخفیف یافت نشد.',
                'error_code' => 'DISCOUNT_NOT_FOUND'
            ];
        }

        $result = Helper::discount_check($code, $categoryId, $userId);
        $responseArr = $result->getData(true);

        if ($responseArr['status']) {
            return [
                'success' => true,
                'message' => $responseArr['error'],
                'data' => [
                    'discount_percent' => $discountCode->discount_percent,
                    'discount_code_id' => $discountCode->id
                ]
            ];
        }

        return [
            'success' => false,
            'message' => $responseArr['error'],
            'error_code' => 'DISCOUNT_INVALID'
        ];
    }

    public function getUserOrders(int $userId): array
    {
        try {
            $orders = $this->repo->getUserOrders($userId);

            // Transform orders to include technician average rating and completed orders count
            $transformedOrders = $orders->map(function ($order) {
                $orderArray = $order->toArray();

                // Add average_rating and completed_orders_count to technician if exists
                if ($order->technician) {
                    $orderArray['technician']['average_rating'] = $order->technician->average_rating
                        ? round($order->technician->average_rating, 2)
                        : null;

                    // اضافه کردن تعداد سفارشات تمام شده تکنسین
                    $orderArray['technician']['completed_orders_count'] = $this->repo->getCompletedOrdersCount($order->technician_id);
                }

                // اضافه کردن اطلاعات تخفیف
                $discountInfo = null;
                if ($order->discountUse && $order->discountUse->discount_code) {
                    $discountInfo = [
                        'code' => $order->discountUse->discount_code->code,
                        'discount_percent' => $order->discountUse->discount_code->discount_percent,
                        'discount_amount' => $order->discount_price,
                    ];
                }
                $orderArray['discount_info'] = $discountInfo;

                return $orderArray;
            });

            return [
                'success' => true,
                'data' => $transformedOrders
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get user orders: ' . $e->getMessage(), [
                'userId' => $userId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست سفارشات'
            ];
        }
    }

    /**
     * دریافت سفارشات کاربر با فیلترهای تاریخ و وضعیت
     */
    public function getUserOrdersWithFilters(
        int $userId,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $status = null,
        ?int $perPage = null
    ): array {
        try {
            $orders = $this->repo->getUserOrdersWithFilters(
                $userId,
                $fromDate,
                $toDate,
                $status,
                $perPage
            );

            // اگر pagination فعال بود
            $isPaginated = $perPage !== null;
            $items = $isPaginated ? $orders->items() : $orders;

            // Transform orders
            $transformedOrders = collect($items)->map(function ($order) {
                $orderArray = $order->toArray();

                // Add average_rating and completed_orders_count to technician if exists
                if ($order->technician) {
                    $orderArray['technician']['average_rating'] = $order->technician->average_rating
                        ? round($order->technician->average_rating, 2)
                        : null;

                    $orderArray['technician']['completed_orders_count'] = $this->repo->getCompletedOrdersCount($order->technician_id);
                }

                // اضافه کردن اطلاعات تخفیف
                $discountInfo = null;
                if ($order->discountUse && $order->discountUse->discount_code) {
                    $discountInfo = [
                        'code' => $order->discountUse->discount_code->code,
                        'discount_percent' => $order->discountUse->discount_code->discount_percent,
                        'discount_amount' => $order->discount_price,
                    ];
                }
                $orderArray['discount_info'] = $discountInfo;

                return $orderArray;
            });

            $result = [
                'success' => true,
                'data' => $transformedOrders,
                'filters' => [
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'status' => $status,
                ]
            ];

            // اضافه کردن اطلاعات pagination
            if ($isPaginated) {
                $result['pagination'] = [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                ];
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to get user orders with filters: ' . $e->getMessage(), [
                'userId' => $userId,
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'status' => $status
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست سفارشات'
            ];
        }
    }

    public function getOrderDetail(int $userId, int $orderId): array
    {
        try {
            $order = $this->repo->getUserOrderDetail($userId, $orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد!',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // Group order details by field_id
            $grouped = $order->details->groupBy('field_id')->map(function ($items, $fieldId) {
                $field = $items->first()->field;
                return [
                    'id' => $field->id,
                    'title' => $field->title,
                    'sort' => $field->sort,
                    'type' => $field->type,
                    'is_required' => $field->is_required,
                    'is_package' => $field->is_package,
                    'image_path' => $field->image_path,
                    'is_final_des' => $field->is_final_des,
                    'icon_name' => $field->icon_name,
                    'guide' => $field->guide,
                    'des' => $field->des,
                    'deleted_at' => $field->deleted_at,
                    'created_at' => $field->created_at,
                    'updated_at' => $field->updated_at,
                    'data' => $items->map(function ($detail) {
                        return [
                            'id' => $detail->id,
                            'type' => $detail->field->type,
                            'order_id' => $detail->order_id,
                            'field_id' => $detail->field_id,
                            'field_detail_id' => $detail->field_detail_id,
                            'value' => $detail->value,
                            'user_descriptions' => $detail->user_descriptions,
                            'price' => $detail->price,
                            'created_at' => $detail->created_at,
                            'updated_at' => $detail->updated_at,
                            'field_detail' => $detail->fieldDetail,
                        ];
                    })->values()
                ];
            })->values();

            // Remove the relation and replace with grouped data
            $order->unsetRelation('details');
            $order->order_details = $grouped;

            // دریافت تعداد پیام‌های خوانده نشده از تکنسین
            $unreadCount = 0;
            if ($order->technician_id) {
                $unreadCount = $this->chatRepo->getUnreadCountByTechnician($userId, $order->technician_id);
            }
            $order->unread_messages_count = $unreadCount;

            // اضافه کردن اطلاعات تخفیف
            $discountInfo = null;
            if ($order->discountUse && $order->discountUse->discount_code) {
                $discountInfo = [
                    'code' => $order->discountUse->discount_code->code,
                    'discount_percent' => $order->discountUse->discount_code->discount_percent,
                    'discount_amount' => $order->discount_price,
                ];
            }
            $order->discount_info = $discountInfo;

            return [
                'success' => true,
                'data' => $order
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get order detail: ' . $e->getMessage(), [
                'userId' => $userId,
                'orderId' => $orderId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت جزئیات سفارش'
            ];
        }
    }

    public function startOrder(int $userId, int $orderId): array
    {
        try {
            $order = $this->repo->getUserOrder($userId, $orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد یا به شما تعلق ندارد.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            if (!$order->arrived_at) {
                return [
                    'success' => false,
                    'message' => 'تکنسین هنوز به محل خدمت نرسیده است.',
                    'error_code' => 'TECHNICIAN_NOT_ARRIVED'
                ];
            }

            $this->repo->updateOrder($orderId, [
                'started_at' => now()
            ]);

            return [
                'success' => true,
                'message' => 'شروع سفارش با موفقیت ثبت شد.'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to start order: ' . $e->getMessage(), [
                'userId' => $userId,
                'orderId' => $orderId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت شروع سفارش'
            ];
        }
    }

    /**
     * شروع کار سفارش توسط تکنسین
     */
    public function startOrderByTechnician(int $technicianId, int $orderId): array
    {
        try {
            $order = $this->repo->getTechnicianOrderDetail($technicianId, $orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد یا به شما اختصاص داده نشده است.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            if (!$order->arrived_at) {
                return [
                    'success' => false,
                    'message' => 'ابتدا باید رسیدن به محل را تایید کنید.',
                    'error_code' => 'TECHNICIAN_NOT_ARRIVED'
                ];
            }

            if ($order->started_at) {
                return [
                    'success' => false,
                    'message' => 'کار این سفارش قبلاً شروع شده است.',
                    'error_code' => 'ORDER_ALREADY_STARTED'
                ];
            }

            $this->repo->updateOrder($orderId, [
                'started_at' => now()
            ]);

            return [
                'success' => true,
                'message' => 'شروع کار با موفقیت ثبت شد.'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to start order by technician: ' . $e->getMessage(), [
                'technicianId' => $technicianId,
                'orderId' => $orderId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت شروع کار'
            ];
        }
    }

    public function endOrder(int $userId, int $orderId): array
    {
        try {
            $order = $this->repo->getUserOrder($userId, $orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد یا به شما تعلق ندارد.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            $this->repo->updateOrder($orderId, [
                'status' => 2,
                'finished_at' => now()
            ]);

            return [
                'success' => true,
                'message' => 'پایان سفارش با موفقیت ثبت شد.'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to end order: ' . $e->getMessage(), [
                'userId' => $userId,
                'orderId' => $orderId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت پایان سفارش'
            ];
        }
    }

    /**
     * پایان کار سفارش توسط تکنسین
     */
    public function endOrderByTechnician(int $technicianId, int $orderId): array
    {
        try {
            $order = $this->repo->getTechnicianOrderDetail($technicianId, $orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد یا به شما اختصاص داده نشده است.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            if (!$order->started_at) {
                return [
                    'success' => false,
                    'message' => 'ابتدا باید کار را شروع کنید.',
                    'error_code' => 'ORDER_NOT_STARTED'
                ];
            }

            if ($order->finished_at) {
                return [
                    'success' => false,
                    'message' => 'این سفارش قبلاً پایان یافته است.',
                    'error_code' => 'ORDER_ALREADY_FINISHED'
                ];
            }

            $this->repo->updateOrder($orderId, [
                'status' => 2,
                'finished_at' => now()
            ]);

            return [
                'success' => true,
                'message' => 'پایان کار با موفقیت ثبت شد.'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to end order by technician: ' . $e->getMessage(), [
                'technicianId' => $technicianId,
                'orderId' => $orderId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت پایان کار'
            ];
        }
    }

    public function cancelOrder(int $userId, int $orderId): array
    {
        try {
            $order = $this->repo->findOrderWithTechnician($orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            if ($order->user_id != $userId) {
                return [
                    'success' => false,
                    'message' => 'این سفارش به شما تعلق ندارد.',
                    'error_code' => 'FORBIDDEN'
                ];
            }

            if ($order->set_off_at) {
                return [
                    'success' => false,
                    'message' => 'تکنسین به محل خدمت اعزام شده است. برای لغو با پشتیبانی تماس بگیرید.',
                    'error_code' => 'TECHNICIAN_DISPATCHED'
                ];
            }

            $this->repo->updateOrder($orderId, [
                'status' => 3
            ]);

            // ارسال پیامک به تکنسین در صورت وجود
            if ($order->technician_id && $order->technician && $order->technician->phone) {
                $smsService = app(SmsService::class);
                // توجه: برای لغو توسط کاربر، از template ID موجود قبلی استفاده می‌شود
                // اگر قالب جدیدی لازم است، باید به لیست اضافه شود
                $technicianName = $order->technician->name ?? 'تکنسین';
                Helper::send_sms(
                    $order->technician->phone,
                    '452241',
                    ['TECHNAME', 'ID'],
                    [$technicianName, $order->id]
                );
            }

            return [
                'success' => true,
                'message' => 'سفارش با موفقیت لغو شد.'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to cancel order: ' . $e->getMessage(), [
                'userId' => $userId,
                'orderId' => $orderId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در لغو سفارش'
            ];
        }
    }

    public function verifyTechnician(int $userId, int $orderId, int $verificationStatus): array
    {
        try {
            $order = $this->repo->getUserOrder($userId, $orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد یا به شما تعلق ندارد.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            if (!$order->technician_id) {
                return [
                    'success' => false,
                    'message' => 'هنوز تکنسینی به این سفارش اختصاص داده نشده است.',
                    'error_code' => 'NO_TECHNICIAN_ASSIGNED'
                ];
            }

            if ($order->is_technician_verified != '0') {
                return [
                    'success' => false,
                    'message' => 'تأیید هویت تکنسین قبلاً انجام شده است.',
                    'error_code' => 'ALREADY_VERIFIED'
                ];
            }

            $this->repo->updateOrder($orderId, [
                'is_technician_verified' => (string) $verificationStatus
            ]);

            $message = $verificationStatus == 1
                ? 'هویت تکنسین با موفقیت تأیید شد.'
                : 'عدم تطابق هویت تکنسین ثبت شد.';

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'order_id' => $orderId,
                    'is_technician_verified' => (string) $verificationStatus
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to verify technician: ' . $e->getMessage(), [
                'userId' => $userId,
                'orderId' => $orderId,
                'verificationStatus' => $verificationStatus
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت تأیید هویت تکنسین'
            ];
        }
    }

    public function getOrderExtraServices(int $userId, int $orderId): array
    {
        try {
            // بررسی مالکیت سفارش
            if (!$this->repo->isUserOrderOwner($userId, $orderId)) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به مشاهده این سفارش نیستید.',
                    'error_code' => 'FORBIDDEN'
                ];
            }

            // دریافت هزینه‌های اضافی
            $extraServices = $this->repo->getOrderExtraServices($orderId);

            return [
                'success' => true,
                'data' => [
                    'order_id' => $orderId,
                    'extra_services' => $extraServices
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch order extra services: ' . $e->getMessage(), [
                'userId' => $userId,
                'orderId' => $orderId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت هزینه‌های اضافی سفارش'
            ];
        }
    }

    /**
     * ثبت پذیرش اولیه کاربر برای سفارش
     */
    public function userInitialAccept(int $userId, int $orderId): array
    {
        try {
            // بررسی مالکیت سفارش
            $order = $this->repo->find($orderId);

            if (!$order || $order->user_id != $userId) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد یا به شما تعلق ندارد.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // بررسی اینکه سفارش کنسل نشده باشد
            if (!$this->repo->isNotCancelled($orderId)) {
                return [
                    'success' => false,
                    'message' => 'این سفارش کنسل شده است و امکان پذیرش اولیه وجود ندارد.',
                    'error_code' => 'ORDER_CANCELLED'
                ];
            }

            // بررسی اینکه user_initial_accept خالی باشد
            if (!$this->repo->isUserInitialAcceptEmpty($orderId)) {
                return [
                    'success' => false,
                    'message' => 'پذیرش اولیه این سفارش قبلاً ثبت شده است.',
                    'error_code' => 'ALREADY_ACCEPTED'
                ];
            }

            // ثبت پذیرش اولیه
            $updated = $this->repo->setUserInitialAccept($orderId);

            if (!$updated) {
                return [
                    'success' => false,
                    'message' => 'خطا در ثبت پذیرش اولیه. لطفاً دوباره تلاش کنید.',
                    'error_code' => 'UPDATE_ERROR'
                ];
            }

            Log::info('پذیرش اولیه کاربر ثبت شد', [
                'user_id' => $userId,
                'order_id' => $orderId
            ]);

            // دریافت سفارش به‌روزرسانی شده
            $order->refresh();

            return [
                'success' => true,
                'message' => 'پذیرش اولیه با موفقیت ثبت شد.',
                'data' => [
                    'order_id' => $orderId,
                    'user_initial_accept' => $order->user_initial_accept?->toISOString(),
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to set user initial accept: ' . $e->getMessage(), [
                'userId' => $userId,
                'orderId' => $orderId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت پذیرش اولیه'
            ];
        }
    }

    /**
     * ثبت تصمیم نهایی کاربر درباره سفارش (تایید یا رد)
     */
    public function userOrderDecision(int $userId, int $orderId, \App\DTOs\UserOrderDecisionDTO $dto): array
    {
        try {
            // بررسی وجود سفارش
            $order = $this->repo->find($orderId);
            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // بررسی مالکیت سفارش
            if ($order->user_id != $userId) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به تصمیم‌گیری درباره این سفارش نیستید.',
                    'error_code' => 'UNAUTHORIZED'
                ];
            }

            // بررسی اینکه قبلاً تصمیم‌گیری نشده باشد
            if ($this->repo->hasUserDecision($orderId)) {
                return [
                    'success' => false,
                    'message' => 'قبلاً درباره این سفارش تصمیم گرفته‌اید.',
                    'error_code' => 'ALREADY_DECIDED'
                ];
            }

            // اگر تصمیم "تایید" باشد
            if ($dto->isAccepted()) {
                $updated = $this->repo->acceptOrder($orderId, $dto->reason);

                if (!$updated) {
                    return [
                        'success' => false,
                        'message' => 'خطا در ثبت تایید سفارش'
                    ];
                }

                Log::info('سفارش توسط کاربر تایید شد', [
                    'user_id' => $userId,
                    'order_id' => $orderId
                ]);

                $order->refresh();

                return [
                    'success' => true,
                    'message' => 'سفارش با موفقیت تایید شد.',
                    'data' => [
                        'order_id' => $orderId,
                        'decision' => 'accepted',
                        'user_accept_date' => $order->user_accept_date?->toISOString(),
                    ]
                ];
            }

            // اگر تصمیم "رد" باشد
            if ($dto->isRejected()) {
                $updated = $this->repo->rejectOrder($orderId, $dto->reason);

                if (!$updated) {
                    return [
                        'success' => false,
                        'message' => 'خطا در ثبت رد سفارش'
                    ];
                }

                Log::info('سفارش توسط کاربر رد شد', [
                    'user_id' => $userId,
                    'order_id' => $orderId,
                    'reason' => $dto->reason
                ]);

                $order->refresh();

                return [
                    'success' => true,
                    'message' => 'سفارش با موفقیت لغو شد.',
                    'data' => [
                        'order_id' => $orderId,
                        'decision' => 'rejected',
                        'status' => $order->status,
                        'user_cancellation_reason' => $order->user_cancellation_reason,
                        'user_cancellation_date' => $order->user_cancellation_date?->toISOString(),
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => 'تصمیم نامعتبر است.'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to process user order decision: ' . $e->getMessage(), [
                'userId' => $userId,
                'orderId' => $orderId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت تصمیم'
            ];
        }
    }

    /**
     * ثبت توضیحات پیگیری بازگشت محصول توسط کاربر
     */
    public function setReturnFollowupDescription(int $userId, int $orderId, string $description): array
    {
        try {
            // بررسی وجود سفارش
            $order = $this->repo->find($orderId);
            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // بررسی مالکیت سفارش
            if ($order->user_id != $userId) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به ثبت توضیحات برای این سفارش نیستید.',
                    'error_code' => 'UNAUTHORIZED'
                ];
            }

            // ثبت توضیحات
            $updated = $this->repo->setReturnFollowupDescription($orderId, $description);

            if (!$updated) {
                return [
                    'success' => false,
                    'message' => 'خطا در ثبت توضیحات'
                ];
            }

            Log::info('توضیحات پیگیری بازگشت توسط کاربر ثبت شد', [
                'user_id' => $userId,
                'order_id' => $orderId
            ]);

            $order->refresh();

            return [
                'success' => true,
                'message' => 'توضیحات پیگیری بازگشت با موفقیت ثبت شد.',
                'data' => [
                    'order_id' => $orderId,
                    'user_return_followup_description' => $order->user_return_followup_description,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Failed to set return followup description: ' . $e->getMessage(), [
                'userId' => $userId,
                'orderId' => $orderId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت توضیحات'
            ];
        }
    }

    /**
     * ثبت توضیحات نهایی کاربر
     */
    public function setUserFinalDescription(int $userId, int $orderId, string $description): array
    {
        try {
            // بررسی وجود سفارش
            $order = $this->repo->find($orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش مورد نظر یافت نشد.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // بررسی مالکیت سفارش
            if ($order->user_id != $userId) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به ثبت توضیحات برای این سفارش نیستید.',
                    'error_code' => 'UNAUTHORIZED'
                ];
            }

            // ثبت توضیحات
            $updated = $this->repo->setUserFinalDescription($orderId, $description);

            if (!$updated) {
                Log::error('خطا در ثبت توضیحات نهایی کاربر', [
                    'user_id' => $userId,
                    'order_id' => $orderId
                ]);

                return [
                    'success' => false,
                    'message' => 'خطا در ثبت توضیحات. لطفاً دوباره تلاش کنید.',
                    'error_code' => 'UPDATE_ERROR'
                ];
            }

            Log::info('توضیحات نهایی کاربر ثبت شد', [
                'user_id' => $userId,
                'order_id' => $orderId
            ]);

            return [
                'success' => true,
                'message' => 'توضیحات نهایی با موفقیت ثبت شد.',
                'data' => [
                    'order_id' => $orderId,
                    'user_final_description' => $description
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در ثبت توضیحات نهایی کاربر', [
                'user_id' => $userId,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت توضیحات'
            ];
        }
    }
    public function setUserInPlaceDescription(int $userId, int $orderId, string $description): array
    {
        try {
            // بررسی وجود سفارش
            $order = $this->repo->find($orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش مورد نظر یافت نشد.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // بررسی مالکیت سفارش
            if ($order->user_id != $userId) {
                return [
                    'success' => false,
                    'message' => 'شما مجاز به ثبت توضیحات برای این سفارش نیستید.',
                    'error_code' => 'UNAUTHORIZED'
                ];
            }

            // ثبت توضیحات
            $updated = $this->repo->setUserInPlaceDescription($orderId, $description);

            if (!$updated) {
                Log::error('خطا در ثبت توضیحات کاربر در محل', [
                    'user_id' => $userId,
                    'order_id' => $orderId
                ]);

                return [
                    'success' => false,
                    'message' => 'خطا در ثبت توضیحات کاربر در محل. لطفاً دوباره تلاش کنید.',
                    'error_code' => 'UPDATE_ERROR'
                ];
            }

            Log::info('توضیحات کاربر در محل ثبت شد', [
                'user_id' => $userId,
                'order_id' => $orderId
            ]);

            return [
                'success' => true,
                'message' => 'توضیحات کاربر در محل با موفقیت ثبت شد.',
                'data' => [
                    'order_id' => $orderId,
                    'user_in_place_description' => $description
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در ثبت توضیحات کاربر در محل', [
                'user_id' => $userId,
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت توضیحات کاربر در محل'
            ];
        }
    }

    /**
     * واریز سهم تکنسین به کیف پول و ثبت تراکنش
     * این متد زمانی فراخوانی می‌شود که سفارش به حالت "انجام شده" تغییر وضعیت می‌دهد
     */
    public function processTechnicianPayment(Order $order, ?string $referenceId = null): array
    {
        try {
            // بررسی وجود تکنسین
            if (!$order->technician_id) {
                return [
                    'success' => false,
                    'message' => 'این سفارش فاقد تکنسین است.',
                    'error_code' => 'NO_TECHNICIAN'
                ];
            }

            // بررسی پرداخت شدن سفارش
            if ($order->payment_status != 1) {
                return [
                    'success' => false,
                    'message' => 'این سفارش هنوز پرداخت نشده است.',
                    'error_code' => 'NOT_PAID'
                ];
            }

            $technician = Technician::find($order->technician_id);

            if (!$technician) {
                return [
                    'success' => false,
                    'message' => 'تکنسین یافت نشد.',
                    'error_code' => 'TECHNICIAN_NOT_FOUND'
                ];
            }

            // بررسی آیا قبلاً تراکنش ثبت شده یا نه (جلوگیری از پرداخت مجدد)
            $existingTransaction = $this->technicianTransactionRepo->getOrderTransactions($order->id);

            if ($existingTransaction->where('type', 1)->where('status', 100)->isNotEmpty()) {
                return [
                    'success' => false,
                    'message' => 'سهم تکنسین قبلاً پرداخت شده است.',
                    'error_code' => 'ALREADY_PAID_TO_TECHNICIAN'
                ];
            }

            DB::beginTransaction();

            try {
                // محاسبه سهم تکنسین
                $technicianPrice = $order->payment_price(false); // بدون قیمت پلتفرم
                $commission = $technician->commission ?? 80; // درصد سهم تکنسین (پیش‌فرض ۸۰٪)
                $technicianShare = $technicianPrice * $commission / 100;

                // واریز به کیف پول تکنسین
                $technician->wallet = ($technician->wallet ?? 0) + $technicianShare;
                $technician->save();

                // ثبت تراکنش تکنسین
                $this->technicianTransactionRepo->createTransaction([
                    'technician_id' => $technician->id,
                    'order_id' => $order->id,
                    'price' => $technicianShare,
                    'commission' => $commission,
                    'referenceId' => $referenceId ?? 'ADMIN_COMPLETE_' . $order->id,
                    'type' => 1, // واریز از سفارش
                    'status' => 100, // موفق
                    'description' => 'واریز سهم از سفارش شماره ' . $order->id,
                ]);

                DB::commit();

                Log::info('سهم تکنسین واریز شد', [
                    'order_id' => $order->id,
                    'technician_id' => $technician->id,
                    'technician_share' => $technicianShare,
                    'commission' => $commission,
                    'reference_id' => $referenceId
                ]);

                return [
                    'success' => true,
                    'message' => 'سهم تکنسین با موفقیت واریز شد.',
                    'data' => [
                        'technician_id' => $technician->id,
                        'amount_paid' => $technicianShare,
                        'commission' => $commission,
                        'new_wallet_balance' => $technician->wallet
                    ]
                ];

            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('خطا در واریز سهم تکنسین', [
                    'order_id' => $order->id,
                    'technician_id' => $technician->id,
                    'error' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'message' => 'خطا در واریز سهم تکنسین',
                    'error_code' => 'PAYMENT_ERROR'
                ];
            }

        } catch (\Exception $e) {
            Log::error('خطا در پردازش پرداخت تکنسین', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در پردازش پرداخت',
                'error_code' => 'PROCESSING_ERROR'
            ];
        }
    }

    /**
     * دریافت لیست خلاصه سفارشات کاربر
     * فقط شامل: created_at, finished_at, referral_code تکنسین, مبلغ پرداخت شده نهایی, نام محصول
     */
    public function getUserOrdersSummary(int $userId): array
    {
        try {
            $orders = $this->repo->getUserOrdersSummary($userId);

            // Transform orders to summary format
            $transformedOrders = $orders->map(function ($order) {
                // محاسبه مبلغ پرداخت شده نهایی
                $finalPrice = ($order->technician_price ?? 0)
                    + ($order->extra_price ?? 0)
                    - ($order->discount_price ?? 0);
                $finalPrice = max(0, $finalPrice); // حداقل صفر
                Log::info($order->category);
                return [
                    'order_id' => $order->id,
                    'created_at' => $order->created_at?->format('Y-m-d H:i:s'),
                    'finished_at' => $order->finished_at?->format('Y-m-d H:i:s'),
                    'technician_referral_code' => $order->technician?->referral_code ?? null,
                    'final_paid_amount' => $finalPrice,
                    'product_name' => $order->technician_order_report?->product_name ?? $order->category?->title,
                    'category_id' => $order->category_id
                ];
            });

            return [
                'success' => true,
                'message' => 'لیست خلاصه سفارشات با موفقیت دریافت شد.',
                'data' => [
                    'orders' => $transformedOrders,
                    'total_count' => $orders->count()
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get user orders summary: ' . $e->getMessage(), [
                'userId' => $userId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست خلاصه سفارشات'
            ];
        }
    }

    /**
     * دریافت جزئیات سفارش برای تکنسین
     */
    public function getTechnicianOrderDetail(int $technicianId, int $orderId): array
    {
        try {
            $order = $this->repo->getTechnicianOrderDetail($technicianId, $orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد یا به شما تخصیص داده نشده است.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // Group order details by field_id
            $grouped = $order->details->groupBy('field_id')->map(function ($items, $fieldId) {
                $field = $items->first()->field;
                return [
                    'id' => $field->id,
                    'title' => $field->title,
                    'sort' => $field->sort,
                    'type' => $field->type,
                    'is_required' => $field->is_required,
                    'is_package' => $field->is_package,
                    'image_path' => $field->image_path,
                    'is_final_des' => $field->is_final_des,
                    'icon_name' => $field->icon_name,
                    'guide' => $field->guide,
                    'des' => $field->des,
                    'deleted_at' => $field->deleted_at,
                    'created_at' => $field->created_at,
                    'updated_at' => $field->updated_at,
                    'data' => $items->map(function ($detail) {
                        return [
                            'id' => $detail->id,
                            'type' => $detail->field->type,
                            'order_id' => $detail->order_id,
                            'field_id' => $detail->field_id,
                            'field_detail_id' => $detail->field_detail_id,
                            'value' => $detail->value,
                            'user_descriptions' => $detail->user_descriptions,
                            'price' => $detail->price,
                            'created_at' => $detail->created_at,
                            'updated_at' => $detail->updated_at,
                            'field_detail' => $detail->fieldDetail,
                        ];
                    })->values()
                ];
            })->values();

            // Remove the relation and replace with grouped data
            $order->unsetRelation('details');
            $order->order_details = $grouped;

            // Add user type information for technician to display
            if ($order->user) {
                $order->user_type_info = [
                    'account_type' => $order->user->account_type ?? 'individual',
                    'account_type_label' => $this->getUserTypeLabel($order->user->account_type ?? 'individual'),
                    'is_special' => $order->user->is_special ?? false,
                ];
            }

            return [
                'success' => true,
                'data' => $order
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get technician order detail: ' . $e->getMessage(), [
                'technicianId' => $technicianId,
                'orderId' => $orderId
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت جزئیات سفارش',
                'error_code' => 'FETCH_ORDER_DETAIL_ERROR'
            ];
        }
    }

    /**
     * لغو سفارش توسط تکنسین
     */
    public function cancelOrderByTechnician(int $technicianId, int $orderId, string $cancelReason): array
    {
        try {
            $order = $this->repo->getTechnicianOrderDetail($technicianId, $orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد یا به شما اختصاص داده نشده است.',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // بررسی اینکه سفارش قبلاً لغو نشده باشد
            if (in_array($order->status, [3, 4, 5, 6])) {
                $statusTexts = [
                    3 => 'کاربر',
                    4 => 'تکنسین',
                    5 => 'ادمین',
                    6 => 'انقضای زمان'
                ];

                return [
                    'success' => false,
                    'message' => 'این سفارش قبلاً توسط ' . ($statusTexts[$order->status] ?? '') . ' لغو شده است.',
                    'error_code' => 'ORDER_ALREADY_CANCELLED'
                ];
            }

            // بررسی اینکه سفارش شروع نشده باشد
            if ($order->started_at) {
                return [
                    'success' => false,
                    'message' => 'سفارش شروع شده است و امکان لغو وجود ندارد. لطفاً سفارش را تکمیل کنید.',
                    'error_code' => 'ORDER_ALREADY_STARTED'
                ];
            }

            // بررسی اینکه سفارش تکمیل نشده باشد
            if ($order->status == 2) {
                return [
                    'success' => false,
                    'message' => 'این سفارش تکمیل شده است و امکان لغو وجود ندارد.',
                    'error_code' => 'ORDER_COMPLETED'
                ];
            }

            DB::beginTransaction();

            try {
                // به‌روزرسانی سفارش
                $this->repo->updateOrder($orderId, [
                    'status' => 4, // لغو توسط تکنسین
                    'technician_cancel_reason' => $cancelReason
                ]);

                // ارسال پیامک به کاربر
                if ($order->user && $order->user->phone) {
                    $smsService = app(SmsService::class);
                    $smsService->sendOrderCancelledByTechnicianToUser($order->user->phone, $order->id);
                }

                // ارسال پیامک به ادمین (از جدول contacts)
                $adminContact = \App\Models\Contact::where('type', 'sms')->first();
                if ($adminContact && $adminContact->link) {
                    $smsService = app(SmsService::class);
                    $smsService->sendOrderCancelledByTechnicianToAdmin($adminContact->link, $order->id);

                    Log::info('پیامک لغو سفارش توسط تکنسین به ادمین ارسال شد', [
                        'order_id' => $order->id,
                        'admin_phone' => $adminContact->link
                    ]);
                }

                DB::commit();

                Log::info('سفارش توسط تکنسین لغو شد', [
                    'technician_id' => $technicianId,
                    'order_id' => $orderId,
                    'reason' => $cancelReason
                ]);

                return [
                    'success' => true,
                    'message' => 'سفارش با موفقیت لغو شد.'
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Failed to cancel order by technician: ' . $e->getMessage(), [
                'technician_id' => $technicianId,
                'order_id' => $orderId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در لغو سفارش',
                'error_code' => 'CANCEL_ORDER_ERROR'
            ];
        }
    }

    /**
     * استخراج داده‌های service_schedule از steps
     */
    private function extractServiceScheduleFromSteps(array $steps): array
    {
        Log::info('=== EXTRACTING SERVICE_SCHEDULE FROM STEPS ===', [
            'steps_count' => count($steps),
            'steps_structure' => $steps
        ]);

        foreach ($steps as $stepIndex => $step) {
            Log::info("Processing step {$stepIndex}", [
                'step_structure' => $step,
                'is_array' => is_array($step),
                'step_keys' => is_array($step) ? array_keys($step) : 'NOT_ARRAY'
            ]);

            foreach ($step as $fieldIndex => $field) {
                Log::info("Processing field {$fieldIndex} in step {$stepIndex}", [
                    'field_structure' => $field,
                    'field_id' => $field['id'] ?? 'NO_ID',
                    'is_service_schedule' => isset($field['id']) && $field['id'] === 'service_schedule'
                ]);

                if (isset($field['id']) && $field['id'] === 'service_schedule') {
                    Log::info('FOUND service_schedule field!', ['field_data' => $field]);
                    return $this->parseServiceScheduleFromField($field);
                }
            }
        }

        Log::info('service_schedule field NOT FOUND in any steps');
        return [];
    }

    /**
     * پردازش فیلد service_schedule و تبدیل به فرمت مناسب
     */
    private function parseServiceScheduleFromField(array $field): array
    {
        Log::info('=== PARSING SERVICE_SCHEDULE FIELD ===', [
            'full_field_structure' => $field,
            'has_field_details' => isset($field['field_details']),
            'field_details_count' => is_array($field['field_details'] ?? null) ? count($field['field_details']) : 0
        ]);

        $result = [];
        $fieldDetails = $field['field_details'] ?? [];

        Log::info('Field details structure:', [
            'field_details' => $fieldDetails
        ]);

        // پیدا کردن نوع انتخاب شده (کوتاه مدت یا بلند مدت)
        $selectedType = null;

        foreach ($fieldDetails as $detail) {
            if ($detail['id'] === 'main_selection' && $detail['type'] === 'radioButton') {
                Log::info('Found main_selection field', [
                    'options' => $detail['options'] ?? 'NO_OPTIONS'
                ]);

                foreach ($detail['options'] ?? [] as $optionIndex => $option) {
                    Log::info("Checking option {$optionIndex}", [
                        'option_data' => $option,
                        'option_id' => $option['id'] ?? 'NO_ID',
                        'option_value' => $option['value'] ?? 'NO_VALUE',
                        'value_equals_1' => ($option['value'] ?? null) == 1,
                        'is_checked' => $option['is_checked'] ?? 'NO_IS_CHECKED'
                    ]);

                    if ($option['value'] == 1) {
                        $selectedType = $option['id']; // short_term یا long_term
                        Log::info('SELECTED TYPE FOUND!', ['selected_type' => $selectedType]);
                        break 2;
                    }
                }
            }
        }

        Log::info('Final selected type result', [
            'selectedType' => $selectedType,
            'will_return_empty' => !$selectedType
        ]);

        if (!$selectedType) {
            Log::info('Returning empty array because no selectedType found');
            return [];
        }

        $result['type'] = $selectedType;

        // استخراج داده‌های مربوط به نوع انتخابی
        if ($selectedType === 'long_term') {
            $result['long_term'] = [];

            foreach ($fieldDetails as $detail) {
                switch ($detail['id']) {
                    case 'long_term_duration':
                        if ($detail['type'] === 'radioButton') {
                            foreach ($detail['options'] ?? [] as $option) {
                                if ($option['value'] == 1) {
                                    $result['long_term']['duration'] = $option['title'];
                                    break;
                                }
                            }
                        }
                        break;

                    case 'long_term_date':
                        if (!empty($detail['value'])) {
                            $result['long_term']['date'] = $detail['value'];
                        }
                        break;

                    case 'long_term_time':
                        if ($detail['type'] === 'radioButton') {
                            foreach ($detail['options'] ?? [] as $option) {
                                if ($option['value'] == 1) {
                                    $result['long_term']['time'] = $option['title'];
                                    break;
                                }
                            }
                        }
                        break;

                    case 'long_term_file':
                        if (!empty($detail['value'])) {
                            $result['long_term']['file'] = $detail['value'];
                        }
                        break;
                }
            }
        } elseif ($selectedType === 'short_term') {
            $result['short_term'] = [];

            foreach ($fieldDetails as $detail) {
                switch ($detail['id']) {
                    case 'short_term_date':
                        if (!empty($detail['value'])) {
                            $result['short_term']['date'] = $detail['value'];
                        }
                        break;

                    case 'short_term_time':
                        if ($detail['type'] === 'radioButton') {
                            foreach ($detail['options'] ?? [] as $option) {
                                if ($option['value'] == 1) {
                                    $result['short_term']['time'] = $option['title'];
                                    break;
                                }
                            }
                        }
                        break;

                    case 'short_term_file':
                        if (!empty($detail['value'])) {
                            $result['short_term']['file'] = $detail['value'];
                        }
                        break;
                }
            }
        }

        return $result;
    }

    /**
     * تبدیل داده‌های service_schedule به فرمت دیتابیس
     */
    private function parseServiceSchedule(array $serviceScheduleData): array
    {
        $result = [];

        // نوع زمان‌بندی
        if (isset($serviceScheduleData['type'])) {
            $result['service_schedule_type'] = $serviceScheduleData['type'];
        }

        // فیلدهای مربوط به بلندمدت
        if (!empty($serviceScheduleData['long_term'])) {
            $longTerm = $serviceScheduleData['long_term'];

            if (isset($longTerm['duration'])) {
                $result['service_schedule_long_duration'] = $longTerm['duration'];
            }
            if (isset($longTerm['date'])) {
                $result['service_schedule_long_date'] = $longTerm['date'];
            }
            if (isset($longTerm['time'])) {
                $result['service_schedule_long_time'] = $longTerm['time'];
            }
            if (isset($longTerm['file'])) {
                $result['service_schedule_long_file'] = $longTerm['file'];
            }
        }

        // فیلدهای مربوط به کوتاه‌مدت
        if (!empty($serviceScheduleData['short_term'])) {
            $shortTerm = $serviceScheduleData['short_term'];

            if (isset($shortTerm['date'])) {
                $result['service_schedule_short_date'] = $shortTerm['date'];
            }
            if (isset($shortTerm['time'])) {
                $result['service_schedule_short_time'] = $shortTerm['time'];
            }
            if (isset($shortTerm['file'])) {
                $result['service_schedule_short_file'] = $shortTerm['file'];
            }
        }

        return $result;
    }

    /**
     * ثبت درخواست کمک اضطراری توسط تکنسین
     * 
     * @param int $orderId
     * @param int $technicianId
     * @param string $emergencyHelp
     * @return array
     */
    public function setEmergencyHelp(int $orderId, int $technicianId, string $emergencyHelp): array
    {
        try {
            // یافتن سفارش
            $order = $this->repo->findById($orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش مورد نظر یافت نشد',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // بررسی اینکه سفارش متعلق به این تکنسین است
            if ($order->technician_id != $technicianId) {
                return [
                    'success' => false,
                    'message' => 'سفارش مورد نظر یافت نشد',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // بررسی اینکه سفارش در حال انجام است
            if ($order->status != 0 && $order->status != 1) {
                return [
                    'success' => false,
                    'message' => 'فقط سفارشات در حال انجام می‌توانند درخواست کمک اضطراری داشته باشند',
                    'error_code' => 'INVALID_ORDER_STATUS'
                ];
            }



            DB::beginTransaction();

            try {
                // به‌روزرسانی سفارش
                $updated = $this->repo->updateEmergencyHelp(
                    $orderId,
                    $emergencyHelp,
                    now()
                );

                if (!$updated) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => 'خطا در ثبت درخواست کمک اضطراری',
                        'error_code' => 'UPDATE_FAILED'
                    ];
                }

                DB::commit();

                Log::info('Emergency help request set successfully', [
                    'order_id' => $orderId,
                    'technician_id' => $technicianId,
                    'emergency_help' => $emergencyHelp
                ]);

                return [
                    'success' => true,
                    'message' => 'درخواست کمک اضطراری با موفقیت ثبت شد',
                    'data' => [
                        'order_id' => $orderId,
                        'emergency_help' => $emergencyHelp,
                        'emergency_help_at' => now()->toIso8601String()
                    ]
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Failed to set emergency help: ' . $e->getMessage(), [
                'technician_id' => $technicianId,
                'order_id' => $orderId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت درخواست کمک اضطراری',
                'error_code' => 'EMERGENCY_HELP_ERROR'
            ];
        }
    }

    /**
     * ثبت نظر تکنسین در پایان سفارش
     * 
     * @param int $orderId
     * @param int $technicianId
     * @param string $technicianOpinion
     * @return array
     */
    public function setTechnicianOpinion(int $orderId, int $technicianId, string $technicianOpinion): array
    {
        try {
            // یافتن سفارش
            $order = $this->repo->findById($orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش مورد نظر یافت نشد',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // بررسی اینکه سفارش متعلق به این تکنسین است
            if ($order->technician_id != $technicianId) {
                return [
                    'success' => false,
                    'message' => 'سفارش مورد نظر یافت نشد',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }

            // بررسی اینکه سفارش تمام شده است (status = 2)
            if ($order->status != 2) {
                return [
                    'success' => false,
                    'message' => 'فقط سفارشات تمام شده می‌توانند نظر تکنسین داشته باشند',
                    'error_code' => 'ORDER_NOT_FINISHED'
                ];
            }

            // بررسی اینکه finished_at پر شده باشد
            if (!$order->finished_at) {
                return [
                    'success' => false,
                    'message' => 'سفارش هنوز به اتمام نرسیده است',
                    'error_code' => 'ORDER_NOT_FINISHED'
                ];
            }

            DB::beginTransaction();

            try {
                // به‌روزرسانی نظر تکنسین
                $updated = $this->repo->updateTechnicianOpinion(
                    $orderId,
                    $technicianOpinion
                );

                if (!$updated) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => 'خطا در ثبت نظر تکنسین',
                        'error_code' => 'UPDATE_FAILED'
                    ];
                }

                DB::commit();

                Log::info('Technician opinion set successfully', [
                    'order_id' => $orderId,
                    'technician_id' => $technicianId,
                    'opinion_length' => strlen($technicianOpinion)
                ]);

                return [
                    'success' => true,
                    'message' => 'نظر تکنسین با موفقیت ثبت شد',
                    'data' => [
                        'order_id' => $orderId,
                        'technician_opinion' => $technicianOpinion
                    ]
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Failed to set technician opinion: ' . $e->getMessage(), [
                'technician_id' => $technicianId,
                'order_id' => $orderId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت نظر تکنسین',
                'error_code' => 'TECHNICIAN_OPINION_ERROR'
            ];
        }
    }

    /**
     * دریافت برچسب فارسی نوع حساب کاربری
     */
    private function getUserTypeLabel(string $accountType): string
    {
        return match ($accountType) {
            'individual' => 'کاربر عادی',
            'organization' => 'سازمان',
            'company' => 'شرکت',
            default => 'کاربر عادی',
        };
    }
}

